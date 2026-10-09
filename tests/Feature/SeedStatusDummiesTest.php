<?php

namespace Tests\Feature;

use App\Models\Appliance;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\ServiceReport;
use App\Models\Transaction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SeedStatusDummiesTest extends TestCase
{
    private const CUSTOMER_ID = 'demo-customer-1';

    private const AUTH_ID = 'a16686ab-d19e-4a1a-9dc6-a0e9a347c215';

    private const BASE_DATE = '2026-10-10';

    private const TABLES = ['customers', 'appliances', 'service_reports', 'service_details', 'appointments', 'transactions'];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.timezone' => 'UTC',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array',
            'services.paymongo.secret_key' => null,
        ]);
        DB::purge();
        Http::preventStrayRequests();
        Notification::fake();
        $this->travelTo(Carbon::parse('2026-10-09 17:30:00', 'UTC'));

        // This command test uses its own in-memory schema and never runs migrations.
        $this->createTable('customers', [
            'first_name' => 'string', 'last_name' => 'string', 'email' => 'string',
            'phone_no' => 'string', 'address' => 'string', 'auth_id' => 'string',
        ], stringId: true);
        $this->createTable('appliances', [
            'customer_id' => 'string', 'product' => 'string', 'brand' => 'string',
            'model_no' => 'string', 'serial_no' => 'string', 'appliance_size' => 'string',
            'date_in' => 'dateTime', 'category' => 'string', 'status' => 'string',
            'purchase_date' => 'date', 'warranty_end' => 'date', 'photo_url' => 'string',
        ]);
        $this->createTable('service_reports', [
            'customer_id' => 'string', 'customer_name' => 'string', 'appliance_id' => 'integer',
            'date_in' => 'date', 'status' => 'string', 'findings' => 'text', 'remarks' => 'text',
            'dealer' => 'string', 'dop' => 'date', 'date_pulled_out' => 'date',
            'used_parts' => 'text', 'attachments' => 'text', 'location' => 'text',
        ]);
        $this->createTable('service_details', [
            'report_id' => 'integer', 'service_types' => 'text', 'service_charge' => 'float',
            'date_repaired' => 'date', 'date_delivered' => 'date', 'complaint' => 'text',
            'labor' => 'float', 'pullout_delivery' => 'float', 'parts_total_charge' => 'float',
            'miscellaneous_cost' => 'float', 'total_amount' => 'float',
            'receptionist' => 'string', 'manager' => 'string', 'technician' => 'string',
            'released_by' => 'string',
        ]);
        $this->createTable('appointments', [
            'customer_id' => 'string', 'title' => 'string', 'appliance_name' => 'string',
            'appointment_date' => 'dateTime', 'time_slot' => 'string',
            'status' => 'string', 'notes' => 'text',
        ], softDeletes: false);
        $this->createTable('transactions', [
            'report_id' => 'integer', 'customer_id' => 'string', 'parts_total' => 'float',
            'labor_total' => 'float', 'total_amount' => 'float', 'partial_payment_amount' => 'float',
            'payment_status' => 'string', 'payment_method' => 'string',
            'reference_no' => 'string', 'received_by' => 'string',
            'paymongo_link_id' => 'string', 'payment_url' => 'string',
            'payment_date' => 'dateTime', 'paid_at' => 'dateTime', 'payment_due' => 'date',
        ]);

        $this->seedCustomer();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_dry_run_reports_the_plan_without_changing_any_rows(): void
    {
        $this->seedExistingRecords();
        $before = $this->snapshot();

        $this->artisan('repairshop:seed-status-dummies', [
            'customer' => self::CUSTOMER_ID, '--date' => self::BASE_DATE, '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame($before, $this->snapshot());
        Http::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_all_scopes_create_the_expected_status_examples_without_model_or_external_side_effects(): void
    {
        foreach ([Customer::class, Appliance::class, ServiceReport::class, Appointment::class, Transaction::class] as $model) {
            Event::listen('eloquent.creating: '.$model, fn () => throw new \RuntimeException('Demo command must not fire model events.'));
        }

        $this->runSuccessfully();
        $this->assertCounts(9, 9, 5, 3);
        $this->assertSame(1, DB::table('customers')->count());
        $statuses = DB::table('service_reports')->pluck('status')->unique()->sort()->values()->all();
        $expected = ServiceReport::STATUSES;
        sort($expected);
        $this->assertSame($expected, $statuses);
        $this->assertSame(
            ['Paid', 'Partial', 'Unpaid'],
            DB::table('transactions')->pluck('payment_status')->sort()->values()->all(),
        );
        $this->assertSame(
            ['Cancelled', 'Completed', 'Confirmed', 'Pending', 'Pending'],
            DB::table('appointments')->pluck('status')->sort()->values()->all(),
        );

        foreach (DB::table('appliances')->get() as $appliance) {
            $this->assertSame(self::CUSTOMER_ID, $appliance->customer_id);
            $this->assertStringStartsWith('[DEMO]', $appliance->product);
            $this->assertStringStartsWith('DEMO-STATUS-v1:'.self::CUSTOMER_ID.':', $appliance->serial_no);
        }
        foreach (DB::table('service_reports')->get() as $report) {
            $this->assertSame(self::CUSTOMER_ID, $report->customer_id);
            $this->assertStringContainsString('DEMO-STATUS-v1:'.self::CUSTOMER_ID.':', $report->remarks);
            $this->assertSame(self::CUSTOMER_ID, DB::table('appliances')->where('id', $report->appliance_id)->value('customer_id'));
        }
        foreach (DB::table('transactions')->get() as $payment) {
            $this->assertSame(self::CUSTOMER_ID, $payment->customer_id);
            $this->assertContains(DB::table('service_reports')->where('id', $payment->report_id)->value('status'), ['Completed', 'Under Repair']);
            $this->assertStringStartsWith('DEMO-STATUS-v1:'.self::CUSTOMER_ID.':', $payment->reference_no);
            $this->assertNull($payment->paymongo_link_id);
            $this->assertNull($payment->payment_url);
            $this->assertContains($payment->payment_method, [null, 'Cash']);
            $this->assertGreaterThan(0, $payment->total_amount);
            if ($payment->payment_status === 'Paid') {
                $this->assertNotNull($payment->paid_at);
                $this->assertNotNull($payment->payment_date);
            } elseif ($payment->payment_status === 'Unpaid') {
                $this->assertNull($payment->paid_at);
                $this->assertNull($payment->payment_date);
            } else {
                $this->assertGreaterThan(0, $payment->partial_payment_amount);
                $this->assertLessThan($payment->total_amount, $payment->partial_payment_amount);
            }
        }
        foreach (DB::table('appointments')->get() as $appointment) {
            $this->assertSame(self::CUSTOMER_ID, $appointment->customer_id);
            $this->assertStringStartsWith('[DEMO]', $appointment->title);
            $this->assertStringContainsString('DEMO-STATUS-v1:'.self::CUSTOMER_ID.':', $appointment->notes);
        }

        Http::assertNothingSent();
        Notification::assertNothingSent();
    }

    #[DataProvider('scopes')]
    public function test_scopes_limit_records_and_repeating_them_changes_nothing(string $scope, array $counts): void
    {
        $this->runSuccessfully(['--scope' => $scope]);
        $this->assertCounts(...$counts);
        $before = $this->snapshot();

        $this->runSuccessfully(['--scope' => $scope, '--date' => '2027-01-01']);

        $this->assertSame($before, $this->snapshot());
    }

    public static function scopes(): array
    {
        return [
            'all' => ['all', [9, 9, 5, 3]],
            'repairs' => ['repairs', [6, 6, 0, 0]],
            'appointments' => ['appointments', [0, 0, 5, 0]],
            'payments' => ['payments', [3, 3, 0, 3]],
        ];
    }

    public function test_combining_scopes_then_all_does_not_duplicate_examples(): void
    {
        foreach (['appointments', 'payments', 'repairs'] as $scope) {
            $this->runSuccessfully(['--scope' => $scope]);
        }
        $before = $this->snapshot();

        $this->runSuccessfully();

        $this->assertCounts(9, 9, 5, 3);
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('exactTargets')]
    public function test_existing_customer_can_be_targeted_by_exact_id_email_or_phone(string $target): void
    {
        $this->runSuccessfully(['customer' => $target, '--scope' => 'appointments']);
        $this->assertCounts(0, 0, 5, 0);
    }

    public static function exactTargets(): array
    {
        return [
            'id' => [self::CUSTOMER_ID],
            'email' => ['leslie@example.test'],
            'phone' => ['+639171234567'],
        ];
    }

    public function test_existing_customer_and_real_records_are_never_changed(): void
    {
        $this->seedExistingRecords();
        $before = $this->snapshot();

        $this->runSuccessfully();
        $after = $this->snapshot();

        foreach ($before as $table => $rows) {
            foreach ($rows as $id => $row) {
                $this->assertSame($row, $after[$table][$id], 'An existing '.$table.' row changed.');
            }
        }
        $this->assertCounts(10, 10, 6, 4);
    }

    public function test_default_base_date_uses_manila_when_the_app_is_configured_for_utc(): void
    {
        $this->artisan('repairshop:seed-status-dummies', [
            'customer' => self::CUSTOMER_ID, '--scope' => 'appointments',
        ])->assertSuccessful();

        $this->assertAppointmentDatesSurround(self::BASE_DATE);
        $schedule = $this->appointmentSchedule();
        DB::table('appointments')->delete();

        $this->runSuccessfully(['--scope' => 'appointments']);

        $this->assertSame($schedule, $this->appointmentSchedule());
    }

    public function test_supplied_base_date_controls_the_future_and_past_appointment_examples(): void
    {
        $this->runSuccessfully(['--scope' => 'appointments', '--date' => '2026-11-15']);
        $this->assertAppointmentDatesSurround('2026-11-15');
    }

    #[DataProvider('unknownTargets')]
    public function test_missing_or_inexact_target_fails_without_writes(string $target): void
    {
        $this->assertFailureLeavesDataUntouched(['customer' => $target]);
    }

    public static function unknownTargets(): array
    {
        return [
            'missing record' => ['missing@example.test'],
            'partial email' => ['leslie'],
            'name' => ['Leslie Reyes'],
            'blank' => ['   '],
        ];
    }

    #[DataProvider('invalidOptions')]
    public function test_invalid_options_fail_without_writes(array $options): void
    {
        $this->assertFailureLeavesDataUntouched($options);
    }

    public static function invalidOptions(): array
    {
        return [
            'unknown scope' => [['--scope' => 'customers']],
            'relative date' => [['--date' => 'tomorrow']],
            'invalid day' => [['--date' => '2026-02-30']],
            'invalid month' => [['--date' => '2026-13-01']],
            'unpadded date' => [['--date' => '2026-1-05']],
            'timestamp instead of date' => [['--date' => '2026-10-09 00:00:00']],
        ];
    }

    #[DataProvider('invalidCustomerStates')]
    public function test_archived_unlinked_or_invalid_auth_customers_are_rejected(array $state): void
    {
        DB::table('customers')->where('id', self::CUSTOMER_ID)->update($state);
        $this->assertFailureLeavesDataUntouched();
    }

    public static function invalidCustomerStates(): array
    {
        return [
            'archived' => [['deleted_at' => '2026-10-01 00:00:00']],
            'unlinked' => [['auth_id' => null]],
            'blank auth' => [['auth_id' => '']],
            'invalid auth' => [['auth_id' => 'not-a-uuid']],
        ];
    }

    public function test_ambiguous_email_is_rejected_even_when_one_match_is_archived(): void
    {
        $this->seedCustomer([
            'id' => 'demo-customer-2', 'auth_id' => 'bfe018d0-2dd2-4736-a8f8-6d3363f406eb',
            'phone_no' => '+639171234568', 'deleted_at' => '2026-10-01 00:00:00',
        ]);

        $this->assertFailureLeavesDataUntouched(['customer' => 'leslie@example.test']);
    }

    #[DataProvider('sharedAuthStates')]
    public function test_customer_sharing_an_auth_link_with_another_customer_is_rejected(bool $archived): void
    {
        $this->seedCustomer([
            'id' => 'demo-customer-2', 'email' => 'other@example.test', 'phone_no' => '+639171234568',
            'deleted_at' => $archived ? '2026-10-01 00:00:00' : null,
        ]);

        $this->assertFailureLeavesDataUntouched();
    }

    public static function sharedAuthStates(): array
    {
        return ['active other customer' => [false], 'archived other customer' => [true]];
    }

    #[DataProvider('archivableDemoTables')]
    public function test_archived_demo_markers_are_not_recreated_or_restored(string $table): void
    {
        $this->runSuccessfully();
        DB::table($table)->where('id', DB::table($table)->min('id'))->update(['deleted_at' => '2026-10-10 00:00:00']);

        $this->assertFailureLeavesDataUntouched();
    }

    public static function archivableDemoTables(): array
    {
        return ['appliance' => ['appliances'], 'report' => ['service_reports'], 'payment' => ['transactions']];
    }

    public function test_failure_during_payment_creation_rolls_back_all_new_demo_rows(): void
    {
        $before = $this->snapshot();
        DB::unprepared("CREATE TRIGGER reject_demo_payment BEFORE INSERT ON transactions BEGIN SELECT RAISE(ABORT, 'Simulated demo payment failure'); END");

        $this->artisan('repairshop:seed-status-dummies', [
            'customer' => self::CUSTOMER_ID, '--date' => self::BASE_DATE,
        ])->assertFailed();

        $this->assertSame($before, $this->snapshot());
        Http::assertNothingSent();
        Notification::assertNothingSent();
    }

    private function createTable(string $name, array $columns, bool $stringId = false, bool $softDeletes = true): void
    {
        Schema::create($name, function (Blueprint $table) use ($columns, $stringId, $softDeletes) {
            $stringId ? $table->string('id')->primary() : $table->id();
            foreach ($columns as $column => $type) {
                $table->{$type}($column)->nullable();
            }
            $table->timestamps();
            if ($softDeletes) {
                $table->softDeletes();
            }
        });
    }

    private function seedCustomer(array $overrides = []): void
    {
        DB::table('customers')->insert(array_replace([
            'id' => self::CUSTOMER_ID, 'first_name' => 'Leslie', 'last_name' => 'Reyes',
            'email' => 'leslie@example.test', 'phone_no' => '+639171234567',
            'address' => 'Davao City', 'auth_id' => self::AUTH_ID,
            'created_at' => '2026-10-01 00:00:00', 'updated_at' => '2026-10-01 00:00:00',
        ], $overrides));
    }

    private function runSuccessfully(array $options = []): void
    {
        $this->artisan('repairshop:seed-status-dummies', array_replace([
            'customer' => self::CUSTOMER_ID, '--date' => self::BASE_DATE,
        ], $options))->assertSuccessful();
    }

    private function assertFailureLeavesDataUntouched(array $options = []): void
    {
        $before = $this->snapshot();

        $this->artisan('repairshop:seed-status-dummies', array_replace([
            'customer' => self::CUSTOMER_ID, '--date' => self::BASE_DATE,
        ], $options))->assertFailed();

        $this->assertSame($before, $this->snapshot());
    }

    private function assertCounts(int $appliances, int $reports, int $appointments, int $payments): void
    {
        foreach (['appliances' => $appliances, 'service_reports' => $reports, 'appointments' => $appointments, 'transactions' => $payments] as $table => $expected) {
            $this->assertSame($expected, DB::table($table)->count(), 'Unexpected '.$table.' count.');
        }
    }

    private function assertAppointmentDatesSurround(string $date): void
    {
        $base = Carbon::parse($date, 'Asia/Manila')->startOfDay();
        $pending = DB::table('appointments')->where('status', 'Pending')->orderBy('appointment_date')->pluck('appointment_date');
        $this->assertCount(2, $pending);
        $this->assertTrue(Carbon::parse($pending[0], 'Asia/Manila')->lessThan($base));
        $this->assertTrue(Carbon::parse($pending[1], 'Asia/Manila')->greaterThan($base));
        $this->assertTrue(Carbon::parse(DB::table('appointments')->where('status', 'Confirmed')->value('appointment_date'), 'Asia/Manila')->greaterThan($base));
        $this->assertTrue(Carbon::parse(DB::table('appointments')->where('status', 'Completed')->value('appointment_date'), 'Asia/Manila')->lessThan($base));
    }

    private function appointmentSchedule(): array
    {
        return DB::table('appointments')->orderBy('notes')
            ->get(['notes', 'status', 'appointment_date', 'time_slot'])
            ->map(fn ($row) => (array) $row)->all();
    }

    private function snapshot(): array
    {
        $snapshot = [];
        foreach (self::TABLES as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->mapWithKeys(fn ($row) => [$row->id => (array) $row])->all();
        }

        return $snapshot;
    }

    private function seedExistingRecords(): void
    {
        $timestamp = '2026-10-01 00:00:00';
        $appliance = DB::table('appliances')->insertGetId([
            'customer_id' => self::CUSTOMER_ID, 'product' => 'Real refrigerator', 'brand' => 'Real Brand',
            'serial_no' => 'REAL-SERIAL-123', 'created_at' => $timestamp, 'updated_at' => $timestamp,
        ]);
        $report = DB::table('service_reports')->insertGetId([
            'customer_id' => self::CUSTOMER_ID, 'customer_name' => 'Leslie Reyes', 'appliance_id' => $appliance,
            'date_in' => '2026-10-01', 'status' => 'Under Repair', 'remarks' => 'Real customer report.',
            'created_at' => $timestamp, 'updated_at' => $timestamp,
        ]);
        DB::table('service_details')->insert([
            'report_id' => $report, 'complaint' => 'Real repair complaint', 'labor' => 800,
            'total_amount' => 800, 'created_at' => $timestamp, 'updated_at' => $timestamp,
        ]);
        DB::table('appointments')->insert([
            'customer_id' => self::CUSTOMER_ID, 'title' => 'Real appointment', 'appointment_date' => '2026-11-01 09:00:00',
            'time_slot' => '09:00', 'status' => 'Pending', 'notes' => 'Real appointment notes.',
            'created_at' => $timestamp, 'updated_at' => $timestamp,
        ]);
        DB::table('transactions')->insert([
            'report_id' => $report, 'customer_id' => self::CUSTOMER_ID, 'total_amount' => 800,
            'payment_status' => 'Unpaid', 'reference_no' => 'REAL-PAYMENT-123',
            'created_at' => $timestamp, 'updated_at' => $timestamp,
        ]);
    }
}
