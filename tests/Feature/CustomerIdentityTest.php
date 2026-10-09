<?php

namespace Tests\Feature;

use App\Models\{Customer, User};
use App\Support\CustomerIdentity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Cache, DB, Http, Notification, Schema};
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerIdentityTest extends TestCase
{
    private const AUTH_ID = 'a16686ab-d19e-4a1a-9dc6-a0e9a347c215';
    private const OTHER_AUTH_ID = 'bfe018d0-2dd2-4736-a8f8-6d3363f406eb';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array',
            'services.supabase.url' => 'https://identity.supabase.test',
            'services.supabase.anon_key' => 'identity-test-anon-key',
        ]);
        DB::purge();
        Cache::flush();
        Http::preventStrayRequests();
        Notification::fake();

        // Only isolated, in-memory tables are used; application migrations never run.
        Schema::create('customers', function (Blueprint $table) {
            $table->string('id')->primary();
            foreach (['first_name', 'last_name', 'email', 'phone_no', 'address', 'profile_picture', 'auth_id', 'deleted_by', 'deletion_reason'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['first_name', 'last_name', 'username', 'email', 'password', 'role', 'status', 'remember_token'] as $column) {
                $table->string($column)->nullable();
            }
            $table->dateTime('email_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('customer_identity_name_key', fn ($first, $last) => CustomerIdentity::nameKey($first, $last), 2);
        $pdo->sqliteCreateFunction('customer_identity_email_key', fn ($email) => CustomerIdentity::emailKey($email), 1);
        $pdo->sqliteCreateFunction('customer_identity_phone_key', fn ($phone) => CustomerIdentity::phoneKey($phone), 1);

        $admin = User::create([
            'first_name' => 'Alex', 'last_name' => 'Santos', 'username' => 'identity.admin',
            'email' => 'admin@example.test', 'password' => 'password',
            'role' => 'Administrator', 'status' => 'Active',
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($admin);
    }

    public function test_create_normalizes_contact_fields_and_keeps_readable_unicode_names(): void
    {
        $this->post(route('customers.store'), $this->input([
            'first_name' => "  María   José  ",
            'last_name' => "  O'Neill-Santos  ",
            'email' => '  MARIA@example.test  ',
            'phone_no' => '+63 (917) 123-4567',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('customers.index'));

        $customer = Customer::sole();
        $this->assertSame('María José', $customer->first_name);
        $this->assertSame("O'Neill-Santos", $customer->last_name);
        $this->assertSame('maria@example.test', $customer->email);
        $this->assertSame('639171234567', $customer->phone_no);
        $this->assertNull($customer->auth_id);
        Http::assertNothingSent();
    }

    #[DataProvider('validUnicodeNames')]
    public function test_name_filter_accepts_letters_marks_and_common_name_punctuation(string $firstName, string $lastName): void
    {
        $this->post(route('customers.store'), $this->input(['first_name' => $firstName, 'last_name' => $lastName]))
            ->assertSessionHasNoErrors()->assertRedirect(route('customers.index'));

        $customer = Customer::sole();
        $this->assertSame($firstName, $customer->first_name);
        $this->assertSame($lastName, $customer->last_name);
    }

    public static function validUnicodeNames(): array
    {
        return [
            'combining accent' => ["Jose\u{0301}", 'Reyes'],
            'marks outside latin combining range' => ['किरण', 'शर्मा'],
            'curly apostrophe and period' => ['Jamie', 'O’Reilly Jr.'],
        ];
    }

    #[DataProvider('duplicateIdentities')]
    public function test_create_rejects_reused_identity_even_if_the_original_is_archived(array $existing, array $input, string $error, bool $archived): void
    {
        $original = $this->seedCustomer($existing, $archived);

        $this->post(route('customers.store'), $this->input($input))->assertSessionHasErrors($error);

        $this->assertSame(1, Customer::withTrashed()->count());
        $this->assertSame($archived, $original->fresh()->trashed());
    }

    public static function duplicateIdentities(): array
    {
        $identities = [
            'name' => [['first_name' => '  MARÍA   JOSÉ ', 'last_name' => ' Dela   Cruz '], ['first_name' => 'maría josé', 'last_name' => 'dela cruz'], 'last_name'],
            'email' => [['email' => ' Owner@Example.Test '], ['email' => 'owner@example.test'], 'email'],
            'local mobile' => [['phone_no' => '0917-123-4567'], ['phone_no' => '+63 (917) 123-4567'], 'phone_no'],
            'country-code mobile' => [['phone_no' => '+63 917 123 4567'], ['phone_no' => '09171234567'], 'phone_no'],
            'mobile without prefix' => [['phone_no' => '9171234567'], ['phone_no' => '639171234567'], 'phone_no'],
        ];
        $cases = [];
        foreach ($identities as $label => $identity) {
            $cases[$label . ' active'] = [...$identity, false];
            $cases[$label . ' archived'] = [...$identity, true];
        }
        return $cases;
    }

    #[DataProvider('invalidIdentityInput')]
    public function test_create_filters_names_and_rejects_invalid_contact_input(string $field, mixed $value): void
    {
        $this->post(route('customers.store'), $this->input([$field => $value]))->assertSessionHasErrors($field);
        $this->assertSame(0, Customer::withTrashed()->count());
    }

    public static function invalidIdentityInput(): array
    {
        return [
            'missing first name' => ['first_name', null],
            'blank first name' => ['first_name', '   '],
            'missing last name' => ['last_name', null],
            'digits in first name' => ['first_name', 'Jamie2'],
            'markup in last name' => ['last_name', '<Reyes>'],
            'punctuation without letters' => ['first_name', '---'],
            'email malformed' => ['email', 'not-an-email'],
            'missing phone' => ['phone_no', null],
            'phone with letters' => ['phone_no', '0917abc4567'],
            'embedded phone plus' => ['phone_no', '0917+1234567'],
            'short phone' => ['phone_no', '123456'],
            'long phone' => ['phone_no', '1234567890123456'],
        ];
    }

    public function test_email_is_optional_and_multiple_customers_can_have_no_email(): void
    {
        $this->seedCustomer(['email' => null]);
        $this->post(route('customers.store'), $this->input(['email' => null]))
            ->assertSessionHasNoErrors()->assertRedirect(route('customers.index'));
        $this->assertSame(2, Customer::count());
        $this->assertSame(2, Customer::whereNull('email')->count());
    }

    public function test_update_allows_its_own_normalized_identity_and_retains_an_omitted_account_link(): void
    {
        $customer = $this->seedCustomer([
            'first_name' => 'Jamie', 'last_name' => 'Reyes', 'email' => 'jamie@example.test',
            'phone_no' => '09171234567', 'auth_id' => self::AUTH_ID,
        ]);

        $this->put(route('customers.update', $customer), $this->input([
            'first_name' => '  JAMIE  ', 'last_name' => ' Reyes ',
            'email' => ' JAMIE@example.test ', 'phone_no' => '+63 917 123 4567',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('customers.index'));

        $this->assertSame(self::AUTH_ID, $customer->fresh()->auth_id);
        $this->assertSame('639171234567', $customer->fresh()->phone_no);
        $this->assertSame('jamie@example.test', $customer->fresh()->email);
        $this->assertSame(1, Customer::count());
    }

    #[DataProvider('archiveStates')]
    public function test_existing_legacy_duplicates_can_update_an_address_without_changing_identity(bool $duplicateArchived): void
    {
        $customer = $this->seedCustomer($this->input(['auth_id' => self::AUTH_ID]));
        $duplicate = $this->seedCustomer([
            'first_name' => '  JAMIE  ', 'last_name' => ' Reyes ',
            'email' => ' JAMIE@Example.Test ', 'phone_no' => '+63 917 123 4567',
        ], $duplicateArchived);
        $duplicateBefore = $duplicate->getAttributes();

        $this->put(route('customers.update', $customer), $this->input([
            'first_name' => ' JAMIE ', 'last_name' => ' Reyes ',
            'email' => ' JAMIE@example.test ', 'phone_no' => '(0917) 123-4567',
            'address' => 'Updated address',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('customers.index'));

        $this->assertSame('Updated address', $customer->fresh()->address);
        $this->assertSame('639171234567', $customer->fresh()->phone_no);
        $this->assertSame('jamie@example.test', $customer->fresh()->email);
        $this->assertSame(self::AUTH_ID, $customer->fresh()->auth_id);
        $this->assertSame($duplicateBefore, $duplicate->fresh()->getAttributes());
        $this->assertSame(2, Customer::withTrashed()->count());
    }

    public function test_legacy_duplicate_groups_do_not_allow_a_third_customer_with_the_same_identity(): void
    {
        $this->seedCustomer($this->input());
        $this->seedCustomer([
            'first_name' => '  JAMIE  ', 'last_name' => ' Reyes ',
            'email' => ' JAMIE@Example.Test ', 'phone_no' => '+63 917 123 4567',
        ]);

        $this->post(route('customers.store'), $this->input())
            ->assertSessionHasErrors(['last_name', 'email', 'phone_no']);

        $this->assertSame(2, Customer::withTrashed()->count());
    }

    #[DataProvider('duplicateIdentities')]
    public function test_update_cannot_take_another_customer_identity(array $existing, array $input, string $error, bool $archived): void
    {
        $this->seedCustomer($existing, $archived);
        $customer = $this->seedCustomer($this->input([
            'first_name' => 'Taylor', 'last_name' => 'Montoya',
            'email' => 'taylor@example.test', 'phone_no' => '09220000000',
        ]));
        $before = $customer->getAttributes();

        $this->put(route('customers.update', $customer), $this->input($input))->assertSessionHasErrors($error);

        $this->assertSame($before, $customer->fresh()->getAttributes());
        $this->assertSame(2, Customer::withTrashed()->count());
    }

    public function test_an_unlinked_customer_can_link_one_unused_mobile_account(): void
    {
        $customer = $this->seedCustomer($this->input());

        $this->put(route('customers.update', $customer), $this->input(['auth_id' => self::AUTH_ID]))
            ->assertSessionHasNoErrors()->assertRedirect(route('customers.index'));
        $this->assertSame(self::AUTH_ID, $customer->fresh()->auth_id);

        $this->put(route('customers.update', $customer), $this->input(['auth_id' => self::AUTH_ID]))
            ->assertSessionHasNoErrors();
        $this->assertSame(self::AUTH_ID, $customer->fresh()->auth_id);
    }

    #[DataProvider('archiveStates')]
    public function test_mobile_account_ids_cannot_be_reused_from_active_or_archived_customers(bool $archived): void
    {
        $owner = $this->seedCustomer(['auth_id' => self::AUTH_ID], $archived);
        $customer = $this->seedCustomer($this->input());

        $this->put(route('customers.update', $customer), $this->input(['auth_id' => self::AUTH_ID]))
            ->assertSessionHasErrors('auth_id');

        $this->assertNull($customer->fresh()->auth_id);
        $this->assertSame(self::AUTH_ID, $owner->fresh()->auth_id);
    }

    public static function archiveStates(): array
    {
        return ['active' => [false], 'archived' => [true]];
    }

    #[DataProvider('replacementAccountIds')]
    public function test_a_linked_account_cannot_be_replaced_or_cleared(?string $replacement): void
    {
        $customer = $this->seedCustomer($this->input(['auth_id' => self::AUTH_ID]));

        $this->put(route('customers.update', $customer), $this->input(['auth_id' => $replacement]))
            ->assertSessionHasErrors('auth_id');

        $this->assertSame(self::AUTH_ID, $customer->fresh()->auth_id);
    }

    public static function replacementAccountIds(): array
    {
        return ['clear' => [null], 'replace' => [self::OTHER_AUTH_ID]];
    }

    public function test_an_invalid_account_id_is_not_persisted(): void
    {
        $customer = $this->seedCustomer($this->input());
        $this->put(route('customers.update', $customer), $this->input(['auth_id' => 'invalid-id']))
            ->assertSessionHasErrors('auth_id');
        $this->assertNull($customer->fresh()->auth_id);
    }

    public function test_mobile_verified_email_can_link_a_single_existing_counter_customer(): void
    {
        $customer = $this->seedCustomer(['email' => '  OWNER@Example.Test  ']);
        $this->fakeSupabaseUser(['email' => 'owner@example.test', 'email_confirmed_at' => now()->toIso8601String()]);

        $this->withToken('verified-counter-account')->getJson('/api/customer/profile')
            ->assertOk()->assertJsonPath('id', $customer->id);

        $this->assertSame(self::AUTH_ID, $customer->fresh()->auth_id);
        $this->assertSame(1, Customer::count());
    }

    public function test_an_existing_mobile_link_resolves_the_same_customer_after_an_email_change(): void
    {
        $customer = $this->seedCustomer(['email' => 'previous@example.test', 'auth_id' => self::AUTH_ID]);
        $this->fakeSupabaseUser(['email' => 'changed@example.test', 'email_confirmed_at' => now()->toIso8601String()]);

        $this->withToken('existing-linked-account')->getJson('/api/customer/profile')
            ->assertOk()->assertJsonPath('id', $customer->id);

        $this->assertSame(self::AUTH_ID, $customer->fresh()->auth_id);
        $this->assertSame(1, Customer::count());
    }

    public function test_mobile_unverified_email_never_claims_a_counter_customer(): void
    {
        $customer = $this->seedCustomer(['email' => 'owner@example.test']);
        $this->fakeSupabaseUser(['email' => 'owner@example.test', 'email_confirmed_at' => null]);

        $this->withToken('unverified-counter-account')->getJson('/api/customer/profile')->assertClientError();

        $this->assertNull($customer->fresh()->auth_id);
        $this->assertSame(1, Customer::count());
    }

    public function test_mobile_verified_phone_can_link_a_single_existing_counter_customer(): void
    {
        $customer = $this->seedCustomer(['email' => null, 'phone_no' => '0918-000-0000']);
        $this->fakeSupabaseUser([
            'email' => null, 'email_confirmed_at' => null,
            'phone' => '+639180000000', 'phone_confirmed_at' => now()->toIso8601String(),
        ]);

        $this->withToken('verified-phone-account')->getJson('/api/customer/profile')
            ->assertOk()->assertJsonPath('id', $customer->id);

        $this->assertSame(self::AUTH_ID, $customer->fresh()->auth_id);
        $this->assertSame(1, Customer::count());
    }

    public function test_mobile_unverified_phone_never_claims_a_counter_customer(): void
    {
        $customer = $this->seedCustomer(['email' => null, 'phone_no' => '09180000000']);
        $this->fakeSupabaseUser([
            'email' => null, 'email_confirmed_at' => null,
            'phone' => '+639180000000', 'phone_confirmed_at' => null,
        ]);

        $this->withToken('unverified-phone-account')->getJson('/api/customer/profile')->assertClientError();

        $this->assertNull($customer->fresh()->auth_id);
        $this->assertSame(1, Customer::count());
    }

    public function test_verified_email_and_phone_matching_the_same_customer_create_one_link(): void
    {
        $customer = $this->seedCustomer(['email' => 'owner@example.test', 'phone_no' => '09180000000']);
        $this->fakeSupabaseUser([
            'email' => 'OWNER@example.test',
            'phone' => '+639180000000', 'phone_confirmed_at' => now()->toIso8601String(),
        ]);

        $this->withToken('verified-email-and-phone-account')->getJson('/api/customer/profile')
            ->assertOk()->assertJsonPath('id', $customer->id);

        $this->assertSame(self::AUTH_ID, $customer->fresh()->auth_id);
        $this->assertSame(1, Customer::count());
    }

    public function test_verified_email_and_phone_matching_different_customers_do_not_claim_either(): void
    {
        $emailMatch = $this->seedCustomer(['email' => 'owner@example.test']);
        $phoneMatch = $this->seedCustomer(['first_name' => 'Different', 'email' => 'other@example.test', 'phone_no' => '09189999999']);
        $this->fakeSupabaseUser([
            'email' => 'owner@example.test',
            'phone' => '+639189999999', 'phone_confirmed_at' => now()->toIso8601String(),
        ]);

        $this->withToken('conflicting-email-and-phone-account')->getJson('/api/customer/profile')->assertClientError();

        $this->assertNull($emailMatch->fresh()->auth_id);
        $this->assertNull($phoneMatch->fresh()->auth_id);
        $this->assertSame(2, Customer::count());
    }

    public function test_mobile_email_match_never_steals_a_customer_linked_to_another_account(): void
    {
        $customer = $this->seedCustomer(['email' => 'owner@example.test', 'auth_id' => self::OTHER_AUTH_ID]);
        $this->fakeSupabaseUser(['email' => 'owner@example.test', 'email_confirmed_at' => now()->toIso8601String()]);

        $this->withToken('conflicting-account')->getJson('/api/customer/profile')->assertClientError();

        $this->assertSame(self::OTHER_AUTH_ID, $customer->fresh()->auth_id);
        $this->assertSame(1, Customer::count());
    }

    public function test_an_archived_profile_does_not_get_relinked_or_returned_to_mobile(): void
    {
        $customer = $this->seedCustomer(['email' => 'owner@example.test'], true);
        $this->fakeSupabaseUser(['email' => 'owner@example.test', 'email_confirmed_at' => now()->toIso8601String()]);

        $this->withToken('archived-customer-account')->getJson('/api/customer/profile')->assertClientError();

        $this->assertNull($customer->fresh()->auth_id);
        $this->assertTrue($customer->fresh()->trashed());
        $this->assertSame(1, Customer::withTrashed()->count());
    }

    public function test_a_previously_linked_archived_account_cannot_claim_another_email_match(): void
    {
        $archived = $this->seedCustomer(['auth_id' => self::AUTH_ID], true);
        $candidate = $this->seedCustomer(['email' => 'new-address@example.test', 'phone_no' => '09189999999', 'first_name' => 'Different']);
        $this->fakeSupabaseUser(['email' => 'new-address@example.test', 'email_confirmed_at' => now()->toIso8601String()]);

        $this->withToken('archived-linked-account')->getJson('/api/customer/profile')->assertClientError();

        $this->assertNull($candidate->fresh()->auth_id);
        $this->assertSame(self::AUTH_ID, $archived->fresh()->auth_id);
    }

    public function test_ambiguous_legacy_email_matches_are_not_automatically_claimed(): void
    {
        $first = $this->seedCustomer(['email' => 'Owner@example.test']);
        $second = $this->seedCustomer(['email' => 'owner@example.test', 'phone_no' => '09189999999', 'first_name' => 'Different']);
        $this->fakeSupabaseUser(['email' => 'owner@example.test', 'email_confirmed_at' => now()->toIso8601String()]);

        $this->withToken('ambiguous-legacy-account')->getJson('/api/customer/profile')->assertClientError();

        $this->assertNull($first->fresh()->auth_id);
        $this->assertNull($second->fresh()->auth_id);
    }

    public function test_ambiguous_legacy_account_links_do_not_return_an_arbitrary_customer(): void
    {
        $first = $this->seedCustomer(['auth_id' => self::AUTH_ID]);
        $second = $this->seedCustomer(['auth_id' => self::AUTH_ID, 'phone_no' => '09189999999', 'first_name' => 'Different']);
        $this->fakeSupabaseUser();

        $this->withToken('ambiguous-legacy-links')->getJson('/api/customer/profile')->assertClientError();

        $this->assertSame(self::AUTH_ID, $first->fresh()->auth_id);
        $this->assertSame(self::AUTH_ID, $second->fresh()->auth_id);
    }

    private function input(array $overrides = []): array
    {
        return array_replace([
            'first_name' => 'Jamie', 'last_name' => 'Reyes', 'email' => 'jamie@example.test',
            'phone_no' => '09171234567', 'address' => 'Davao City',
        ], $overrides);
    }

    private function seedCustomer(array $overrides = [], bool $archived = false): Customer
    {
        $id = (string) Str::uuid();
        // Raw insert deliberately keeps historical formatting that must also be checked.
        DB::table('customers')->insert(array_replace([
            'id' => $id, 'first_name' => 'Original', 'last_name' => 'Owner',
            'email' => 'original@example.test', 'phone_no' => '09180000000',
            'created_at' => now(), 'updated_at' => now(),
            'deleted_at' => $archived ? now() : null,
        ], $overrides));

        return Customer::withTrashed()->findOrFail($id);
    }

    private function fakeSupabaseUser(array $overrides = []): void
    {
        Http::fake([
            'https://identity.supabase.test/auth/v1/user' => Http::response(array_replace([
                'id' => self::AUTH_ID,
                'email' => 'owner@example.test',
                'email_confirmed_at' => now()->toIso8601String(),
            ], $overrides)),
        ]);
    }
}
