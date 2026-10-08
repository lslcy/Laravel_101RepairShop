<?php

namespace Tests\Feature\Support;

use App\Models\{Appliance, Customer, Part, ServiceReport, Transaction, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\{DB, Http, Notification, Schema};
use Tests\TestCase;

abstract class BillingTestCase extends TestCase
{
    protected Customer $customer;
    protected Appliance $appliance;
    protected Part $part;
    protected int $linksCreated = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array',
            'services.paymongo.secret_key' => null,
        ]);
        DB::purge();
        Http::preventStrayRequests();
        Notification::fake();
        $this->withoutExceptionHandling();

        // Build only this feature's schema in memory; application migrations are never run.
        $this->createTable('customers', ['first_name' => 'string', 'last_name' => 'string', 'email' => 'string', 'phone_no' => 'string', 'address' => 'string'], true);
        $this->createTable('users', ['first_name' => 'string', 'last_name' => 'string', 'username' => 'string', 'email' => 'string', 'password' => 'string', 'role' => 'string', 'status' => 'string', 'email_verified_at' => 'dateTime']);
        $this->createTable('appliances', ['customer_id' => 'string', 'product' => 'string', 'brand' => 'string', 'appliance_size' => 'string', 'warranty_end' => 'date']);
        $this->createTable('parts', ['part_no' => 'string', 'name' => 'string', 'price' => 'float', 'quantity_stock' => 'integer']);
        $this->createTable('service_reports', ['customer_id' => 'string', 'customer_name' => 'string', 'appliance_id' => 'integer', 'date_in' => 'date', 'status' => 'string', 'findings' => 'text', 'remarks' => 'text', 'dealer' => 'string', 'dop' => 'date', 'used_parts' => 'text', 'attachments' => 'text', 'location' => 'text']);
        $this->createTable('service_details', ['report_id' => 'integer', 'service_types' => 'text', 'service_charge' => 'float', 'date_repaired' => 'date', 'date_delivered' => 'date', 'complaint' => 'text', 'labor' => 'float', 'pullout_delivery' => 'float', 'parts_total_charge' => 'float', 'miscellaneous_cost' => 'float', 'total_amount' => 'float', 'receptionist' => 'string', 'manager' => 'string', 'technician' => 'string', 'released_by' => 'string']);
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('report_id');
            $table->string('customer_id');
            foreach (['parts_total', 'labor_total', 'total_amount', 'partial_payment_amount'] as $column) $table->float($column)->nullable();
            foreach (['payment_status', 'payment_method', 'reference_no', 'received_by', 'paymongo_link_id', 'payment_url'] as $column) $table->string($column)->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->date('payment_due')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('part_service_report', function (Blueprint $table) {
            $table->id();
            $table->integer('part_id');
            $table->integer('service_report_id');
            $table->integer('quantity');
            $table->float('price');
            $table->timestamps();
        });

        $admin = User::create(['first_name' => 'Alex', 'last_name' => 'Santos', 'username' => 'billing.admin', 'email' => 'admin@example.test', 'password' => 'password', 'role' => 'Administrator', 'status' => 'Active']);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($admin);
        $this->customer = Customer::create(['first_name' => 'Jamie', 'last_name' => 'Reyes', 'email' => 'jamie@example.test', 'phone_no' => '09171234567']);
        $this->appliance = Appliance::create(['customer_id' => $this->customer->id, 'product' => 'Washing Machine', 'brand' => 'Panasonic', 'appliance_size' => 'Large']);
        $this->part = Part::create(['part_no' => 'P-001', 'name' => 'Drain Pump', 'price' => 150, 'quantity_stock' => 10]);
    }

    private function createTable(string $name, array $columns, bool $stringId = false): void
    {
        Schema::create($name, function (Blueprint $table) use ($columns, $stringId) {
            $stringId ? $table->string('id')->primary() : $table->id();
            foreach ($columns as $column => $type) $table->{$type}($column)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function serviceInput(array $overrides = []): array
    {
        return array_replace([
            'customer_id' => $this->customer->id,
            'appliance_id' => $this->appliance->id,
            'date_in' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'problem_desc' => 'Does not drain',
            'labor_cost' => 500,
            'miscellaneous_cost' => 25,
            'service_types' => ['Repair'],
            'technicians' => [],
            'parts' => [['id' => $this->part->id, 'quantity' => 2, 'price' => '150.00']],
        ], $overrides);
    }

    protected function createService(array $overrides = []): ServiceReport
    {
        $this->post(route('services.store'), $this->serviceInput($overrides))
            ->assertSessionHasNoErrors()->assertRedirect(route('services.index'));
        return ServiceReport::latest('id')->firstOrFail();
    }

    protected function pendingBill(ServiceReport $report): Transaction
    {
        return $report->transactions()->sole();
    }

    protected function fakePayMongo(?callable $onCreate = null): void
    {
        config(['services.paymongo.secret_key' => 'sk_test_billing_fixture']);
        Http::fake(function (Request $request) use ($onCreate) {
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/v1/payment_links')) {
                if ($onCreate) $onCreate($request);
                $id = 'plink_billing_' . ++$this->linksCreated;
                return Http::response(['data' => ['id' => $id, 'url' => 'https://checkout.paymongo.test/' . $id]], 201);
            }
            if ($request->method() === 'PATCH' && str_contains($request->url(), '/v1/payment_links/')) {
                return Http::response(['data' => ['id' => basename($request->url()), 'status' => 'archived']], 200);
            }
            throw new \RuntimeException('Unexpected vendor request: ' . $request->method() . ' ' . $request->url());
        });
    }
}
