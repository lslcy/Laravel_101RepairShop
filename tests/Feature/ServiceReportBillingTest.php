<?php

namespace Tests\Feature;

use App\Models\{Customer, ServiceReport, Transaction};
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\{DB, Event, Http};
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Support\BillingTestCase;

class ServiceReportBillingTest extends BillingTestCase
{
    public function test_saving_a_report_immediately_creates_one_pending_bill_with_its_full_cost(): void
    {
        $report = $this->createService();
        $bill = $this->pendingBill($report);

        $this->assertSame('Pending', $bill->payment_status);
        $this->assertSame($this->customer->id, $bill->customer_id);
        $this->assertSame('System', $bill->received_by);
        $this->assertEquals(825, $bill->total_amount);
        $this->assertEquals(500, $bill->labor_total);
        $this->assertEquals(300, $bill->parts_total);
        $this->assertEquals($report->details->total_amount, $bill->total_amount);
        $this->assertNull($bill->paid_at);
        $this->assertNull($bill->payment_date);
        $this->assertNull($bill->payment_method);
        $this->assertNull($bill->payment_url);
        $this->assertEquals(8, $this->part->fresh()->quantity_stock);
        Http::assertNothingSent();
    }

    public function test_the_online_link_uses_the_already_persisted_bill_amount_and_identifiers(): void
    {
        $this->fakePayMongo(function (Request $request) {
            $bill = Transaction::sole();
            $this->assertSame(82500, $request['amount']);
            $this->assertSame('PHP', $request['currency']);
            $this->assertEquals($bill->id, $request['metadata']['transaction_id']);
            $this->assertEquals($bill->report_id, $request['metadata']['report_id']);
            $this->assertEquals($bill->total_amount * 100, $request['amount']);
        });

        $bill = $this->pendingBill($this->createService());
        $this->assertSame('plink_billing_1', $bill->paymongo_link_id);
        $this->assertSame('https://checkout.paymongo.test/plink_billing_1', $bill->payment_url);
        $this->assertSame('Pending', $bill->payment_status);
        $this->assertNull($bill->paid_at);
        $this->assertSame(1, $this->linksCreated);
    }

    public function test_report_edits_synchronize_the_existing_bill_before_regenerating_its_link(): void
    {
        $observed = [];
        $this->fakePayMongo(function (Request $request) use (&$observed) {
            $bill = Transaction::sole();
            $this->assertEquals($bill->total_amount * 100, $request['amount']);
            $observed[] = ['id' => $bill->id, 'amount' => $request['amount'], 'customer' => $bill->customer_id];
        });
        $report = $this->createService();
        $original = $this->pendingBill($report);
        $otherCustomer = Customer::create(['first_name' => 'Taylor', 'last_name' => 'Cruz', 'phone_no' => '09170000001']);
        $changed = $this->serviceInput(['customer_id' => $otherCustomer->id, 'labor_cost' => 700, 'miscellaneous_cost' => 75, 'parts' => [['id' => $this->part->id, 'quantity' => 3, 'price' => 150]]]);

        $this->put(route('services.update', $report), $changed)->assertSessionHasNoErrors()->assertRedirect(route('services.index'));

        $bill = $this->pendingBill($report->fresh());
        $this->assertSame($original->id, $bill->id);
        $this->assertSame($otherCustomer->id, $bill->customer_id);
        $this->assertEquals(1225, $bill->total_amount);
        $this->assertEquals(700, $bill->labor_total);
        $this->assertEquals(450, $bill->parts_total);
        $this->assertEquals($report->fresh()->details->total_amount, $bill->total_amount);
        $this->assertSame('plink_billing_2', $bill->paymongo_link_id);
        $this->assertSame(7, (int) $this->part->fresh()->quantity_stock);
        $this->assertSame([82500, 122500], array_column($observed, 'amount'));
        $this->assertSame([$original->id, $original->id], array_column($observed, 'id'));

        // Re-saving unchanged costs keeps one bill and the same usable checkout link.
        $this->put(route('services.update', $report), $changed)->assertSessionHasNoErrors();
        $this->assertSame(2, $this->linksCreated);
        $this->assertSame(1, Transaction::count());
        $this->assertSame(7, (int) $this->part->fresh()->quantity_stock);
    }

    public function test_an_existing_unpaid_bill_is_updated_without_changing_its_status(): void
    {
        $report = $this->createService();
        $bill = $this->pendingBill($report);
        $bill->update(['payment_status' => 'Unpaid']);

        $this->put(route('services.update', $report), $this->serviceInput(['labor_cost' => 900]))->assertSessionHasNoErrors();

        $this->assertSame('Unpaid', $bill->fresh()->payment_status);
        $this->assertEquals(1225, $bill->fresh()->total_amount);
        $this->assertSame(1, Transaction::count());
    }

    #[DataProvider('settledStatuses')]
    public function test_report_cost_edits_do_not_rewrite_paid_or_partial_receipts(string $status): void
    {
        $report = $this->createService();
        $bill = $this->pendingBill($report);
        $bill->update(['payment_status' => $status, 'payment_method' => 'Cash', 'partial_payment_amount' => $status === 'Partial' ? 200 : null, 'paid_at' => $status === 'Paid' ? now() : null, 'received_by' => 'Alex Santos']);
        $before = $bill->fresh()->getAttributes();

        $this->put(route('services.update', $report), $this->serviceInput(['labor_cost' => 1000, 'miscellaneous_cost' => 50]))->assertSessionHasNoErrors();

        $this->assertSame($before, $bill->fresh()->getAttributes());
        $this->assertEquals(1350, $report->fresh()->details->total_amount);
        $this->assertSame(1, Transaction::count());
        Http::assertNothingSent();
    }

    public static function settledStatuses(): array
    {
        return ['paid' => ['Paid'], 'partial' => ['Partial']];
    }

    public function test_failure_to_create_the_bill_rolls_back_the_report_details_parts_and_stock(): void
    {
        Event::listen('eloquent.creating: ' . Transaction::class, function () {
            throw new \RuntimeException('Bill persistence failed');
        });

        try {
            $this->post(route('services.store'), $this->serviceInput());
            $this->fail('Expected the bill persistence failure to propagate.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Bill persistence failed', $error->getMessage());
        }

        $this->assertSame(0, ServiceReport::count());
        $this->assertSame(0, DB::table('service_details')->count());
        $this->assertSame(0, DB::table('part_service_report')->count());
        $this->assertSame(0, Transaction::count());
        $this->assertSame(10, (int) $this->part->fresh()->quantity_stock);
    }

    public function test_a_gateway_failure_keeps_the_pending_bill_and_saved_service(): void
    {
        config(['services.paymongo.secret_key' => 'sk_test_billing_fixture']);
        Http::fake(['https://api.paymongo.com/*' => Http::response(['errors' => [['detail' => 'Gateway unavailable']]], 503)]);

        $report = $this->createService();
        $bill = $this->pendingBill($report);
        $this->assertSame('Pending', $bill->payment_status);
        $this->assertEquals(825, $bill->total_amount);
        $this->assertNull($bill->payment_url);
        $this->assertSame(8, (int) $this->part->fresh()->quantity_stock);
    }

    public function test_failure_to_sync_the_bill_rolls_back_service_cost_and_stock_changes(): void
    {
        $report = $this->createService();
        $bill = $this->pendingBill($report);
        $before = $bill->getAttributes();
        Event::listen('eloquent.updating: ' . Transaction::class, function () {
            throw new \RuntimeException('Bill update failed');
        });

        try {
            $this->put(route('services.update', $report), $this->serviceInput(['labor_cost' => 900, 'parts' => [['id' => $this->part->id, 'quantity' => 3, 'price' => 150]]]));
            $this->fail('Expected the bill synchronization failure to propagate.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Bill update failed', $error->getMessage());
        }

        $this->assertSame($before, $bill->fresh()->getAttributes());
        $this->assertEquals(825, $report->fresh()->details->total_amount);
        $this->assertEquals(500, $report->fresh()->details->labor);
        $this->assertSame(8, (int) $this->part->fresh()->quantity_stock);
        $this->assertSame(2, (int) DB::table('part_service_report')->value('quantity'));
    }

    public function test_editing_a_report_with_an_archived_receipt_does_not_create_a_replacement_bill(): void
    {
        $report = $this->createService();
        $bill = $this->pendingBill($report);
        $bill->delete();
        $before = Transaction::withTrashed()->findOrFail($bill->id)->getAttributes();

        $this->put(route('services.update', $report), $this->serviceInput(['labor_cost' => 900]))->assertSessionHasNoErrors();

        $this->assertSame(0, Transaction::count());
        $this->assertSame(1, Transaction::withTrashed()->count());
        $this->assertSame($before, Transaction::withTrashed()->findOrFail($bill->id)->getAttributes());
    }

    #[DataProvider('settledStatuses')]
    public function test_manual_payment_reuses_the_pending_bill_instead_of_creating_a_second_transaction(string $status): void
    {
        $report = $this->createService(['status' => 'Completed', 'miscellaneous_cost' => 0]);
        $bill = $this->pendingBill($report);

        $this->post(route('transactions.store'), [
            'report_id' => $report->id, 'labor' => 500, 'materials' => 300, 'delivery' => 0,
            'payment_status' => $status, 'payment_method' => 'Cash',
            'partial_payment_amount' => $status === 'Partial' ? 200 : null,
            'reference_no' => 'RCPT-001',
        ])->assertSessionHasNoErrors()->assertRedirect(route('transactions.index'));

        $updated = $this->pendingBill($report->fresh());
        $this->assertSame($bill->id, $updated->id);
        $this->assertSame(1, Transaction::count());
        $this->assertSame($status, $updated->payment_status);
        $this->assertSame('Cash', $updated->payment_method);
        $this->assertEquals(800, $updated->total_amount);
        if ($status === 'Paid') {
            $this->assertNotNull($updated->paid_at);
            $this->assertNotNull($updated->payment_date);
        } else {
            $this->assertEquals(200, $updated->partial_payment_amount);
        }
    }

    public function test_a_manual_payment_cannot_duplicate_an_already_paid_report(): void
    {
        $report = $this->createService(['status' => 'Completed']);
        $bill = $this->pendingBill($report);
        $bill->update(['payment_status' => 'Paid', 'payment_method' => 'Cash', 'paid_at' => now()]);
        $before = $bill->fresh()->getAttributes();

        $this->withExceptionHandling()->from(route('transactions.create'))->post(route('transactions.store'), [
            'report_id' => $report->id, 'labor' => 500, 'materials' => 300,
            'payment_status' => 'Paid', 'payment_method' => 'Cash',
        ])->assertSessionHasErrors('report_id');

        $this->assertSame(1, Transaction::count());
        $this->assertSame($before, $bill->fresh()->getAttributes());
    }

    public function test_the_immediate_bill_does_not_bypass_the_completed_report_guard_for_manual_payments(): void
    {
        $report = $this->createService();
        $bill = $this->pendingBill($report);
        $before = $bill->getAttributes();

        $this->post(route('transactions.store'), [
            'report_id' => $report->id, 'labor' => 500, 'materials' => 300,
            'payment_status' => 'Paid', 'payment_method' => 'Cash',
        ])->assertSessionHas('error');

        $this->assertSame(1, Transaction::count());
        $this->assertSame($before, $bill->fresh()->getAttributes());
    }

    public function test_editing_a_pending_transaction_regenerates_its_link_for_the_saved_amount(): void
    {
        $this->fakePayMongo(function (Request $request) {
            $this->assertEquals(Transaction::sole()->total_amount * 100, $request['amount']);
        });
        $report = $this->createService();
        $bill = $this->pendingBill($report);

        $this->put(route('transactions.update', $bill), ['total_amount' => 1000, 'payment_status' => 'Pending'])
            ->assertSessionHasNoErrors()->assertRedirect(route('transactions.index'));

        $updated = $bill->fresh();
        $this->assertSame('Pending', $updated->payment_status);
        $this->assertEquals(1000, $updated->total_amount);
        $this->assertSame('plink_billing_2', $updated->paymongo_link_id);
        $this->assertNull($updated->payment_date);
        $this->assertNull($updated->paid_at);
        $this->assertSame(1, Transaction::count());
        $this->assertSame(2, $this->linksCreated);
    }

    public function test_cancelling_a_report_disables_and_retires_its_existing_payment_link(): void
    {
        $this->fakePayMongo();
        $report = $this->createService();
        $bill = $this->pendingBill($report);

        $this->put(route('services.update', $report), $this->serviceInput(['status' => 'Cancelled']))->assertSessionHasNoErrors();

        $this->assertSame('Cancelled', $report->fresh()->status);
        $this->assertNull($bill->fresh()->payment_url);
        $this->assertNull($bill->fresh()->paymongo_link_id);
        $this->assertSame(1, $this->linksCreated);
        Http::assertSent(fn (Request $request) => $request->method() === 'PATCH'
            && str_ends_with($request->url(), '/v1/payment_links/plink_billing_1')
            && $request['archive'] === true);
    }

    public function test_cancelling_a_partially_paid_report_retires_its_balance_link_without_rewriting_the_receipt(): void
    {
        $this->fakePayMongo();
        $report = $this->createService(['status' => 'Completed']);
        $bill = $this->pendingBill($report);
        $this->post(route('transactions.store'), [
            'report_id' => $report->id, 'labor' => 500, 'materials' => 300,
            'delivery' => 0, 'payment_status' => 'Partial', 'partial_payment_amount' => 200,
            'payment_method' => 'GCash', 'reference_no' => 'PARTIAL-VERIFIED-001',
            'payment_date' => now()->subDay()->format('Y-m-d'),
        ])->assertSessionHasNoErrors();
        $partial = $bill->fresh();
        $this->assertSame('plink_billing_2', $partial->paymongo_link_id);
        $this->assertNotNull($partial->payment_url);
        $receiptFields = ['report_id', 'customer_id', 'payment_status', 'partial_payment_amount',
            'total_amount', 'parts_total', 'labor_total', 'payment_method', 'payment_date',
            'paid_at', 'reference_no', 'received_by'];
        $before = \Illuminate\Support\Arr::only($partial->getAttributes(), $receiptFields);

        $this->put(route('services.update', $report), $this->serviceInput(['status' => 'Cancelled']))->assertSessionHasNoErrors();

        $cancelled = $bill->fresh();
        $this->assertNull($cancelled->payment_url);
        $this->assertNull($cancelled->paymongo_link_id);
        $this->assertSame($before, \Illuminate\Support\Arr::only($cancelled->getAttributes(), $receiptFields));
        $this->assertSame(2, $this->linksCreated);
        $this->assertSame(1, Transaction::count());
        Http::assertSent(fn (Request $request) => $request->method() === 'PATCH'
            && str_ends_with($request->url(), '/v1/payment_links/plink_billing_2')
            && $request['archive'] === true);
    }

    public function test_report_cost_sync_preserves_delivery_already_recorded_with_an_unpaid_bill(): void
    {
        $report = $this->createService(['status' => 'Completed']);
        $bill = $this->pendingBill($report);
        $this->post(route('transactions.store'), [
            'report_id' => $report->id, 'labor' => 500, 'materials' => 300,
            'delivery' => 60, 'payment_status' => 'Unpaid',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(885, $bill->fresh()->total_amount);

        $this->put(route('services.update', $report), $this->serviceInput(['status' => 'Completed', 'labor_cost' => 700]))->assertSessionHasNoErrors();

        $this->assertEquals(60, $report->fresh()->details->pullout_delivery);
        $this->assertEquals(1085, $report->fresh()->details->total_amount);
        $this->assertEquals(1085, $bill->fresh()->total_amount);
        $this->assertSame($bill->id, $this->pendingBill($report->fresh())->id);
    }

    #[DataProvider('manualOnlineMethods')]
    public function test_admin_confirmation_records_online_payment_on_the_existing_pending_bill(string $method): void
    {
        $report = $this->createService();
        $bill = $this->pendingBill($report);
        $this->assertSame('Pending', $bill->payment_status);
        $this->assertNull($bill->paid_at);
        $paidDate = now()->subDay()->format('Y-m-d');

        $this->put(route('transactions.update', $bill), [
            'payment_status' => 'Paid', 'payment_method' => $method,
            'reference_no' => 'ONLINE-VERIFIED-001', 'payment_date' => $paidDate,
        ])->assertSessionHasNoErrors()->assertRedirect(route('transactions.index'));

        $verified = $bill->fresh();
        $this->assertSame($bill->id, $verified->id);
        $this->assertSame(1, Transaction::count());
        $this->assertSame('Paid', $verified->payment_status);
        $this->assertSame($method, $verified->payment_method);
        $this->assertSame('ONLINE-VERIFIED-001', $verified->reference_no);
        $this->assertSame('Alex Santos', $verified->received_by);
        $this->assertSame($paidDate, $verified->payment_date->format('Y-m-d'));
        $this->assertNotNull($verified->paid_at);
        $this->assertEquals(825, $verified->total_amount);
    }

    public static function manualOnlineMethods(): array
    {
        return ['GCash' => ['GCash'], 'Card' => ['Card']];
    }

    public function test_provider_callbacks_cannot_mark_a_pending_bill_paid_with_or_without_a_signature(): void
    {
        $this->fakePayMongo();
        $bill = $this->pendingBill($this->createService());
        $before = $bill->getAttributes();
        $body = json_encode(['data' => ['type' => 'event', 'attributes' => [
            'type' => 'link.payment.paid', 'livemode' => false,
            'data' => ['id' => 'pay_fixture', 'type' => 'payment', 'attributes' => [
                'link_id' => $bill->paymongo_link_id, 'amount' => 82500,
                'currency' => 'PHP', 'status' => 'paid', 'source' => ['type' => 'gcash'],
            ]],
        ]]], JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = 't=' . $timestamp . ',te=' . hash_hmac('sha256', $timestamp . '.' . $body, 'unused_fixture_secret') . ',li=';

        foreach ([null, $signature] as $header) {
            $server = ['CONTENT_TYPE' => 'application/json'];
            if ($header !== null) $server['HTTP_PAYMONGO_SIGNATURE'] = $header;
            $this->call('POST', route('webhooks.paymongo'), [], [], [], $server, $body)->assertOk();
            $this->assertSame($before, $bill->fresh()->getAttributes());
        }

        $this->assertSame('Pending', $bill->fresh()->payment_status);
        $this->assertNull($this->appliance->fresh()->warranty_end);
    }
}
