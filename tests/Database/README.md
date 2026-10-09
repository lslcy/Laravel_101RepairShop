The customer identity regression checks execute the actual migration SQL in an
in-memory PostgreSQL engine. They do not load Laravel configuration or connect to
Supabase. They cover direct mobile writes, sign-up rollback, archived identities,
preserved legacy duplicates, name filtering, and migration rollback.

From the repository root, install the optional test engine in a temporary folder
and run the checks:

```powershell
New-Item -ItemType Directory -Force storage/framework/customer-identity-check
Set-Content storage/framework/customer-identity-check/.gitignore '*'
npm.cmd install --prefix storage/framework/customer-identity-check --no-save --no-package-lock --ignore-scripts @electric-sql/pglite
node tests/Database/customer-identity-check.mjs
```

Laravel form and API checks use SQLite in memory:

```powershell
C:\xampp\php\php.exe artisan test --compact --filter CustomerIdentityTest
```
