import { createRequire } from 'node:module';
import { resolve } from 'node:path';
import assert from 'node:assert/strict';
import { readFileSync, writeFileSync } from 'node:fs';
import { randomUUID } from 'node:crypto';

// Use an isolated optional test dependency, never the application's database.
const testRequire = createRequire(resolve('storage/framework/customer-identity-check/package.json'));
const { PGlite } = testRequire('@electric-sql/pglite');

const migration = readFileSync('database/migrations/2026_10_09_000000_enforce_unique_customer_accounts.php', 'utf8');
const [up, down] = [...migration.matchAll(/DB::unprepared\(<<<'SQL'\r?\n([\s\S]*?)\r?\nSQL\);/g)].map(match => match[1]);
assert(up && down);
const db = new PGlite();
const checks = [];
const failures = [];
const check = async (name, fn) => {
  try { await fn(); checks.push(name); console.log('PASS', name); }
  catch (error) { failures.push({ name, message: error.message }); console.log('FAIL', name, error.message); }
};
const one = async (sql, params = []) => (await db.query(sql, params)).rows[0];
const expectError = async (fn, code) => {
  let caught;
  try { await fn(); } catch (error) { caught = error; }
  assert(caught, 'Expected database rejection');
  if (code) assert.equal(caught.code, code);
};
const customer = async (values = {}) => {
  const id = randomUUID();
  const valuesWithId = { id, ...values };
  const columns = Object.keys(valuesWithId);
  await db.query(`INSERT INTO customers (${columns.join(',')}) VALUES (${columns.map((_, i) => '$' + (i + 1)).join(',')})`, Object.values(valuesWithId));
  return id;
};
const signup = async (email, phone = null, metadata = {}) => {
  const id = randomUUID();
  await db.query('INSERT INTO auth.users(id,email,phone,raw_user_meta_data) VALUES ($1,$2,$3,$4)', [id, email, phone, JSON.stringify(metadata)]);
  return id;
};
const count = async table => Number((await one(`SELECT count(*) AS count FROM ${table}`)).count);
await db.exec(`
CREATE SCHEMA auth;
CREATE TABLE auth.users(id uuid PRIMARY KEY, email text, phone text, raw_user_meta_data jsonb);
CREATE TABLE public.customers(
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(), auth_id uuid,
  first_name text, last_name text, email text, phone_no text, address text,
  created_at timestamptz DEFAULT now(), updated_at timestamptz DEFAULT now(), deleted_at timestamptz
);
CREATE FUNCTION public.handle_new_user() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RETURN NEW; END; $$;
CREATE TRIGGER existing_auth_signup_binding AFTER INSERT ON auth.users FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();
ALTER TABLE public.customers ENABLE ROW LEVEL SECURITY;
CREATE POLICY existing_customer_policy ON public.customers FOR SELECT USING (true);
`);
const legacyInvalid = await customer({first_name:'Customer 1',last_name:'Legacy',email:'legacy@example.test',phone_no:'ext 123'});
await db.transaction(tx => tx.exec(up));
const originalCount = await count('customers');

await check('migration preserves existing RLS and Auth trigger', async () => {
  assert.equal((await one("SELECT count(*)::int AS count FROM pg_policies WHERE tablename='customers' AND policyname='existing_customer_policy'")).count, 1);
  assert.equal((await one("SELECT count(*)::int AS count FROM pg_trigger WHERE tgname='existing_auth_signup_binding'")).count, 1);
});

await check('legacy names and phones do not block unrelated updates or archiving', async () => {
  await db.query('UPDATE customers SET address=$1,deleted_at=now() WHERE id=$2',['Legacy address',legacyInvalid]);
  const row = await one('SELECT * FROM customers WHERE id=$1',[legacyInvalid]);
  assert.equal(row.first_name,'Customer 1'); assert.equal(row.phone_no,'ext 123'); assert(row.deleted_at);
  await db.query('UPDATE customers SET deleted_at=NULL WHERE id=$1',[legacyInvalid]);
  await expectError(() => db.query('UPDATE customers SET first_name=$1 WHERE id=$2',['Customer 2',legacyInvalid]),'23514');
  await expectError(() => db.query('UPDATE customers SET phone_no=$1 WHERE id=$2',['ext 456',legacyInvalid]),'23514');
});
await check('legacy counter profile can receive its one account link', async () => {
  const auth = await signup('legacy@example.test');
  assert.equal((await one('SELECT auth_id FROM customers WHERE id=$1',[legacyInvalid])).auth_id,auth);
});

await check('name keys collapse Unicode boundary and internal spaces', async () => {
  for (const space of ['\u00a0', '\u2007', '\u202f', '\u3000']) {
    const row = await one('SELECT customer_identity_name_key($1,$2) AS key', [space+'Jane'+space+'Marie'+space, space+'Doe'+space]);
    assert.equal(row.key,'jane marie doe');
  }
});

let jane;
await check('normalizes readable names and contact details', async () => {
  jane = await customer({first_name:'  Jane   Marie ',last_name:" O’Neill-Doe ",email:' JANE@example.test ',phone_no:'+63 (917) 123-4567'});
  const row = await one('SELECT * FROM customers WHERE id=$1', [jane]);
  assert.equal(row.first_name, 'Jane Marie'); assert.equal(row.last_name,'O’Neill-Doe');
  assert.equal(row.email,'jane@example.test'); assert.equal(row.phone_no,'639171234567');
});

for (const [name, values] of [
  ['name case and spacing', {first_name:' jane  marie ',last_name:'o’neill-doe',email:'new@example.test',phone_no:'09180000001'}],
  ['full name across field split', {first_name:'Jane',last_name:'Marie O’Neill-Doe',email:'split@example.test',phone_no:'09180000002'}],
  ['email case and trim', {first_name:'Other',last_name:'Email',email:'  JANE@EXAMPLE.TEST ',phone_no:'09180000003'}],
  ['Philippine local mobile format', {first_name:'Other',last_name:'Phone',email:'phone@example.test',phone_no:'09171234567'}],
  ['Philippine short mobile format', {first_name:'Other',last_name:'Phone',email:'phone@example.test',phone_no:'9171234567'}],
  ['Philippine international dial prefix', {first_name:'Other',last_name:'Phone',email:'phone@example.test',phone_no:'00639171234567'}],
]) await check('rejects duplicate '+name, () => expectError(() => customer(values), '23505'));

await check('one account ID cannot be assigned to two customers', async () => {
  const auth = randomUUID();
  const id = await customer({first_name:'Account',last_name:'Owner',auth_id:auth});
  await expectError(() => customer({first_name:'Account',last_name:'Impostor',auth_id:auth}), '23505');
  await expectError(() => db.query('UPDATE customers SET auth_id=NULL WHERE id=$1',[id]), '23514');
  await expectError(() => db.query('UPDATE customers SET auth_id=$1 WHERE id=$2',[randomUUID(),id]), '23514');
  await db.query('UPDATE customers SET address=$1 WHERE id=$2',['New address',id]);
});

const archived = await customer({first_name:'Archived',last_name:'Owner',email:'archive@example.test',phone_no:'09181111111',deleted_at:new Date().toISOString()});
for (const [name, values] of [
  ['name',{first_name:' archived ',last_name:'OWNER'}],
  ['email',{email:'ARCHIVE@example.test'}],
  ['phone',{phone_no:'+639181111111'}],
]) await check('archived '+name+' remains reserved', () => expectError(() => customer(values),'23505'));

await check('email signup links the counter record without another customer', async () => {
  const before = await count('customers');
  const auth = await signup('JANE@example.test',null,{first_name:'Jane Marie',last_name:'O’Neill-Doe',phone_no:'09171234567',address:'Counter address'});
  assert.equal(await count('customers'),before);
  assert.equal((await one('SELECT auth_id FROM customers WHERE id=$1',[jane])).auth_id,auth);
});
await check('another signup cannot take a linked counter account', () => expectError(() => signup('jane@example.test'), '23505'));
await check('archived customer signup is refused', () => expectError(() => signup('archive@example.test'), '23505'));
await check('duplicate name with new email refuses the entire auth signup', async () => {
  const before = await count('auth.users');
  await expectError(() => signup('name-clone@example.test',null,{first_name:'jane marie',last_name:'o’neill-doe'}), '23505');
  assert.equal(await count('auth.users'),before);
});
await check('unverified metadata phone cannot claim a counter customer', () => expectError(() => signup('phone-clone@example.test',null,{first_name:'New',last_name:'Person',phone_no:'09171234567'}),'23505'));

await check('phone OTP signup reads Auth phone without metadata', async () => {
  const auth = await signup(null,'+639192222222');
  const row = await one('SELECT * FROM customers WHERE auth_id=$1',[auth]);
  assert.equal(row.phone_no,'639192222222'); assert.equal(row.first_name,null);
});
await check('verified phone channel can link a counter profile', async () => {
  const id = await customer({first_name:'Phone',last_name:'Counter',phone_no:'09193333333'});
  const before = await count('customers');
  const auth = await signup(null,'+639193333333');
  assert.equal(await count('customers'),before);
  assert.equal((await one('SELECT auth_id FROM customers WHERE id=$1',[id])).auth_id,auth);
});
await check('phone channel cannot make a second account for a linked profile', () => expectError(() => signup(null,'+639193333333'),'23505'));
await check('OAuth may complete missing names later', async () => {
  const auth = await signup('oauth@example.test');
  await db.query("UPDATE customers SET first_name='OAuth',last_name='Person' WHERE auth_id=$1",[auth]);
});

await check('email and phone selecting different counter customers is refused', async () => {
  await customer({email:'mixed@example.test'});
  await customer({phone_no:'09194444444'});
  await expectError(() => signup('mixed@example.test','+639194444444'),'23505');
});
await check('counter link cannot smuggle another customer metadata identity', async () => {
  await customer({email:'unused-counter@example.test'});
  await expectError(() => signup('unused-counter@example.test',null,{first_name:'Archived',last_name:'Owner'}),'23505');
  await expectError(() => signup('unused-counter@example.test',null,{phone_no:'09181111111'}),'23505');
});

let independent = await customer({first_name:'Independent',last_name:'Person',email:'independent@example.test',phone_no:'09195555555'});
for (const [name, statement, params] of [
  ['name',"UPDATE customers SET first_name='Jane Marie', last_name='O’Neill-Doe' WHERE id=$1",[independent]],
  ['email',"UPDATE customers SET email='JANE@example.test' WHERE id=$1",[independent]],
  ['phone',"UPDATE customers SET phone_no='09171234567' WHERE id=$1",[independent]],
]) await check('direct mobile update cannot reuse '+name, () => expectError(() => db.query(statement,params),'23505'));

for (const [name, value] of [ ['digits','Jane123'],['emoji','Jane😀'],['punctuation only','...'],['symbols','Jane@'] ]) {
  await check('database filters invalid name '+name, () => expectError(() => customer({first_name:value}),'23514'));
  await check('signup filters invalid metadata name '+name, () => expectError(() => signup(randomUUID()+'@example.test',null,{first_name:value}),'23514'));
}
for (const [name, value] of [ ['accent','María José'],['combining accent','Mari\u0301a'],['Devanagari marks','अनुष्का'],['curly punctuation','J. O’Neill'] ]) {
  await check('accepts Unicode name '+name, () => customer({first_name:value}));
}
await check('database rejects invalid phone letters', () => expectError(() => customer({phone_no:'abc09175555555'}),'23514'));
await check('signup rejects invalid phone letters', () => expectError(() => signup('badphone@example.test',null,{phone_no:'abc09175555555'}),'23514'));
await check('database rejects misplaced plus signs', () => expectError(() => customer({phone_no:'09+176666666'}),'23514'));
await check('signup rejects misplaced plus signs', () => expectError(() => signup('badplus@example.test',null,{phone_no:'09+171234567'}),'23514'));

await check('rollback restores prior behavior and preserves data', async () => {
  const before = await count('customers');
  await db.transaction(tx => tx.exec(down));
  assert.equal(await count('customers'),before);
  assert.equal((await one("SELECT count(*)::int AS count FROM pg_indexes WHERE indexname LIKE 'customers_identity_%'")).count,0);
  assert.equal((await one("SELECT count(*)::int AS count FROM pg_trigger WHERE tgname='existing_auth_signup_binding'")).count,1);
});
for (const [name, first, second] of [
  ['name',{first_name:'Jane',last_name:'Doe'},{first_name:' jane ',last_name:'DOE'}],
  ['email',{email:'owner@example.test'},{email:' OWNER@EXAMPLE.TEST '}],
  ['phone',{phone_no:'09171234567'},{phone_no:'+639171234567'}],
  ['auth', {auth_id:'bfe018d0-2dd2-4736-a8f8-6d3363f406eb'}, {auth_id:'bfe018d0-2dd2-4736-a8f8-6d3363f406eb'}],
]) await check('existing duplicate '+name+' is preserved while future duplicates are blocked', async () => {
  await db.exec('TRUNCATE customers, auth.users');
  await customer(first); await customer({...second,deleted_at:new Date().toISOString()});
  await db.transaction(tx => tx.exec(up));
  assert.equal(await count('customers'),2);
  await expectError(() => customer(first),'23505');
  assert.equal(await count('customers'),2);
  await db.query('UPDATE customers SET address=$1',['Legacy maintenance']);
  assert.equal(await count('customers'),2);
  await db.transaction(tx => tx.exec(down));
});
await db.close();
const result = {passed:checks.length,failures};
writeFileSync('storage/framework/customer-identity-check/postgres-results.json',JSON.stringify(result,null,2));
console.log(JSON.stringify(result));
process.exitCode = failures.length ? 1 : 0;
