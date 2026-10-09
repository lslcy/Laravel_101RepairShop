<?php

namespace Tests\Feature;

use App\Models\CustomerPaymentSubmission;
use App\Models\ServiceReport;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CustomerPaymentReviewService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Feature\Support\BillingTestCase;

class CustomerPaymentReviewTest extends BillingTestCase
{
    private CustomerPaymentReviewService $payments;

    private Transaction $bill;

    private CustomerPaymentSubmission $receipt;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->withoutVite();
        config([
            'app.timezone' => 'UTC',
            'services.supabase.url' => 'https://project.supabase.test',
            'services.supabase.service_key' => 'server-test-key',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-10-09 18:30:00', 'UTC'));
        Cache::put('sidebar_pending_appointments', 0, 60);
        Schema::create('notifications', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('customer_payment_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('transaction_id');
            $table->uuid('auth_id');
            $table->string('customer_id');
            $table->string('method');
            $table->string('status');
            $table->decimal('amount', 12, 2);
            $table->string('receipt_path')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewed_by')->nullable();
            $table->text('review_note')->nullable();
        });
        Schema::create('customer_payment_settings', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->text('gcash_qr_image_url');
            $table->string('gcash_recipient_name');
        });
        $report = ServiceReport::create([
            'customer_id' => $this->customer->id,
            'customer_name' => 'Jamie Reyes',
            'appliance_id' => $this->appliance->id,
            'status' => 'Completed',
        ]);
        $this->bill = Transaction::create([
            'report_id' => $report->id,
            'customer_id' => $this->customer->id,
            'total_amount' => 2000.75,
            'partial_payment_amount' => 600.50,
            'payment_status' => 'Partial',
            'payment_method' => 'Cash',
            'received_by' => 'System',
        ]);
        $id = (string) Str::uuid();
        $authId = (string) Str::uuid();
        $this->receipt = CustomerPaymentSubmission::create([
            'id' => $id,
            'transaction_id' => $this->bill->id,
            'customer_id' => $this->customer->id,
            'auth_id' => $authId,
            'method' => 'gcash',
            'status' => 'pending',
            'amount' => '1400.25',
            'receipt_path' => $authId.'/'.$this->bill->id.'/'.$id.'.png',
            'created_at' => now(),
        ]);
        $this->administrator = User::where('role', 'Administrator')->sole();
        $this->payments = app(CustomerPaymentReviewService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function staff(string $role): User
    {
        $staff = User::create([
            'first_name' => 'Sam', 'last_name' => 'Staff', 'username' => strtolower($role),
            'email' => strtolower($role).'@example.test', 'password' => 'password',
            'role' => $role, 'status' => 'Active',
        ]);
        $staff->forceFill(['email_verified_at' => now()])->save();

        return $staff;
    }

    private function imageBytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACklEQVR42mNgAAAAAgABSK+kcQAAAABJRU5ErkJggg==');
    }

    public function test_approval_records_remaining_balance_as_paid_with_manila_date_and_warranty(): void
    {
        $this->post(route('customer-payments.review', $this->receipt), [
            'decision' => 'approved', 'reference_no' => '  GCASH-12345  ',
        ])->assertSessionHasNoErrors()->assertRedirect(route('customer-payments.show', $this->receipt));

        $bill = $this->bill->fresh();
        $this->assertSame('Paid', $bill->payment_status);
        $this->assertSame('GCash', $bill->payment_method);
        $this->assertSame('GCASH-12345', $bill->reference_no);
        $this->assertSame('Alex Santos', $bill->received_by);
        $this->assertNull($bill->partial_payment_amount);
        $this->assertEquals(2000.75, $bill->total_amount);
        $this->assertSame('2026-10-10', $bill->payment_date->toDateString());
        $this->assertSame('2026-10-09 18:30:00', $bill->paid_at->format('Y-m-d H:i:s'));
        $this->assertSame('approved', $this->receipt->fresh()->status);
        $this->assertStringContainsString('Administrator #'.$this->administrator->id.' Alex Santos', $this->receipt->fresh()->reviewed_by);
        $this->assertSame('2027-04-09', $this->appliance->fresh()->warranty_end);
        $this->assertSame(1, Transaction::count());
        Http::assertNothingSent();
    }

    public function test_rejection_keeps_financial_values_and_warranty_unchanged(): void
    {
        $before = $this->bill->fresh()->getAttributes();
        $this->post(route('customer-payments.review', $this->receipt), [
            'decision' => 'rejected', 'note' => ' The screenshot does not show a reference number. ',
        ])->assertSessionHasNoErrors();

        $this->assertSame('rejected', $this->receipt->fresh()->status);
        $this->assertSame('The screenshot does not show a reference number.', $this->receipt->fresh()->review_note);
        $this->assertSame($before, $this->bill->fresh()->getAttributes());
        $this->assertNull($this->appliance->fresh()->warranty_end);
    }

    public function test_rejection_requires_a_reason_for_the_customer(): void
    {
        $this->post(route('customer-payments.review', $this->receipt), ['decision' => 'rejected', 'note' => ' '])
            ->assertSessionHasErrors('note');
        $this->assertSame('pending', $this->receipt->fresh()->status);
        $this->assertSame('Partial', $this->bill->fresh()->payment_status);
    }

    public function test_changed_balance_cannot_be_approved_but_can_be_rejected(): void
    {
        $this->bill->update(['total_amount' => 2500]);
        $this->post(route('customer-payments.review', $this->receipt), ['decision' => 'approved'])
            ->assertSessionHasErrors('decision');
        $this->assertSame('pending', $this->receipt->fresh()->status);
        $this->assertSame('Partial', $this->bill->fresh()->payment_status);
        $this->assertNull($this->appliance->fresh()->warranty_end);
        $this->post(route('customer-payments.review', $this->receipt), ['decision' => 'rejected', 'note' => 'The bill changed; please review the updated balance.'])
            ->assertSessionHasNoErrors();
    }

    public function test_duplicate_approval_cannot_change_an_already_recorded_receipt(): void
    {
        $this->payments->review($this->receipt, $this->administrator, 'approved', null, 'FIRST');
        $bill = $this->bill->fresh()->getAttributes();
        $this->post(route('customer-payments.review', $this->receipt), ['decision' => 'approved', 'reference_no' => 'SECOND'])
            ->assertSessionHasErrors('decision');
        $this->assertSame($bill, $this->bill->fresh()->getAttributes());
        $this->assertSame('FIRST', $this->bill->fresh()->reference_no);
    }

    public function test_approval_of_an_already_paid_or_archived_bill_is_blocked(): void
    {
        $this->bill->update(['payment_status' => 'Paid']);
        $this->post(route('customer-payments.review', $this->receipt), ['decision' => 'approved'])->assertSessionHasErrors('decision');
        $this->bill->update(['payment_status' => 'Partial']);
        $this->bill->delete();
        $this->post(route('customer-payments.review', $this->receipt), ['decision' => 'approved'])->assertSessionHasErrors('decision');
        $this->assertSame('pending', $this->receipt->fresh()->status);
    }

    public function test_pay_at_shop_preference_cannot_be_approved_as_a_gcash_receipt(): void
    {
        $this->receipt->forceFill(['method' => 'shop', 'status' => 'pay_at_shop', 'receipt_path' => null])->save();
        $this->post(route('customer-payments.review', $this->receipt), ['decision' => 'approved'])->assertSessionHasErrors('decision');
        $this->assertSame('Partial', $this->bill->fresh()->payment_status);
        $this->assertSame('pay_at_shop', $this->receipt->fresh()->status);
    }

    public function test_admin_service_gate_also_blocks_a_direct_cashier_review(): void
    {
        $cashier = $this->staff('Cashier');
        $this->expectException(AuthorizationException::class);
        $this->payments->review($this->receipt, $cashier, 'approved');
    }

    public function test_cashier_and_secretary_cannot_view_receipts_review_or_change_settings(): void
    {
        foreach (['Cashier', 'Secretary'] as $role) {
            $this->actingAs($this->staff($role));
            $this->get(route('customer-payments.index'))->assertForbidden();
            $this->get(route('customer-payments.show', $this->receipt))->assertForbidden();
            $this->get(route('customer-payments.receipt', $this->receipt))->assertForbidden();
            $this->post(route('customer-payments.review', $this->receipt), ['decision' => 'approved'])->assertForbidden();
            $this->get(route('customer-payments.settings.edit'))->assertForbidden();
            $this->put(route('customer-payments.settings.update'), ['gcash_recipient_name' => 'Other'])->assertForbidden();
        }
        $this->assertSame('pending', $this->receipt->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_receipt_is_proxied_privately_without_storage_credentials_in_browser_content(): void
    {
        Http::fake(['https://project.supabase.test/storage/v1/object/authenticated/payment-receipts/*' => Http::response($this->imageBytes(), 200, ['Content-Type' => 'image/png'])]);
        $response = $this->get(route('customer-payments.receipt', $this->receipt))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame($this->imageBytes(), $response->getContent());
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer server-test-key') && $request->hasHeader('apikey', 'server-test-key'));
        $this->get(route('customer-payments.show', $this->receipt))->assertOk()
            ->assertSee(route('customer-payments.receipt', $this->receipt), false)
            ->assertDontSee('server-test-key')->assertDontSee($this->receipt->receipt_path);
    }

    public function test_receipt_proxy_refuses_another_submissions_storage_path(): void
    {
        $this->receipt->forceFill(['receipt_path' => $this->receipt->auth_id.'/'.$this->bill->id.'/'.Str::uuid().'.png'])->save();
        $this->get(route('customer-payments.receipt', $this->receipt))->assertStatus(503);
        Http::assertNothingSent();
        $this->post(route('customer-payments.review', $this->receipt), ['decision' => 'approved'])->assertSessionHasErrors('decision');
        $this->assertSame('Partial', $this->bill->fresh()->payment_status);
    }

    public function test_receipt_proxy_refuses_html_and_storage_redirects(): void
    {
        Http::fake(['https://project.supabase.test/storage/v1/*' => Http::response('<html>not an image</html>', 200)]);
        $this->get(route('customer-payments.receipt', $this->receipt))->assertStatus(503);
        Http::fake(['https://project.supabase.test/storage/v1/*' => Http::response('', 302, ['Location' => 'https://other.test/receipt'])]);
        $this->get(route('customer-payments.receipt', $this->receipt))->assertStatus(503);
    }

    public function test_image_storage_missing_config_does_not_send_a_request(): void
    {
        config(['services.supabase.service_key' => null]);
        $this->get(route('customer-payments.receipt', $this->receipt))->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_payment_queue_and_details_render_with_privacy_safe_receipt_route(): void
    {
        $this->get(route('customer-payments.index'))->assertOk()->assertSee('Jamie Reyes')->assertSee('Pending review');
        $this->get(route('customer-payments.show', $this->receipt))->assertOk()->assertSee('Approve and mark paid')->assertDontSee('server-test-key');
        $this->get(route('customer-payments.index', ['status' => 'all', 'search' => (string) $this->bill->id]))->assertOk()->assertSee('Jamie Reyes');
    }

    public function test_qr_settings_uploads_an_actual_image_and_uses_lco_recipient(): void
    {
        Http::fake(['https://project.supabase.test/storage/v1/object/shop-payment-qr/*' => Http::response(['Key' => 'saved'], 200)]);
        $this->put(route('customer-payments.settings.update'), [
            'gcash_recipient_name' => 'L. C. O.', 'qr_image' => UploadedFile::fake()->image('shop-qr.png', 40, 40),
        ])->assertSessionHasNoErrors()->assertRedirect(route('customer-payments.settings.edit'));
        $settings = DB::table('customer_payment_settings')->sole();
        $this->assertSame('L. C. O.', $settings->gcash_recipient_name);
        $this->assertStringStartsWith('https://project.supabase.test/storage/v1/object/public/shop-payment-qr/gcash/', $settings->gcash_qr_image_url);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST' && $request->hasHeader('Authorization', 'Bearer server-test-key') && $request->hasHeader('Content-Type', 'image/png'));
        $this->get(route('customer-payments.settings.edit'))->assertOk()->assertSee('L. C. O.')->assertDontSee('server-test-key');
    }

    public function test_first_qr_setup_requires_a_supported_image_and_failed_upload_keeps_previous_settings(): void
    {
        $this->put(route('customer-payments.settings.update'), ['gcash_recipient_name' => 'L. C. O.'])->assertSessionHasErrors('qr_image');
        $this->put(route('customer-payments.settings.update'), ['gcash_recipient_name' => 'L. C. O.', 'qr_image' => UploadedFile::fake()->create('qr.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('qr_image');
        DB::table('customer_payment_settings')->insert(['id' => 1, 'gcash_recipient_name' => 'L. C. O.', 'gcash_qr_image_url' => 'https://example.test/original.png']);
        Http::fake(['https://project.supabase.test/storage/v1/*' => Http::response([], 503)]);
        $this->put(route('customer-payments.settings.update'), ['gcash_recipient_name' => 'Other', 'qr_image' => UploadedFile::fake()->image('qr.png')])->assertSessionHasErrors('qr_image');
        $this->assertSame('L. C. O.', DB::table('customer_payment_settings')->sole()->gcash_recipient_name);
        $this->assertSame('https://example.test/original.png', DB::table('customer_payment_settings')->sole()->gcash_qr_image_url);
    }

    public function test_recipient_update_keeps_existing_qr_image_without_uploading_again(): void
    {
        DB::table('customer_payment_settings')->insert(['id' => 1, 'gcash_recipient_name' => 'Shop', 'gcash_qr_image_url' => 'https://example.test/original.png']);
        $this->put(route('customer-payments.settings.update'), ['gcash_recipient_name' => ' L. C. O. '])->assertSessionHasNoErrors();
        $this->assertSame('L. C. O.', DB::table('customer_payment_settings')->sole()->gcash_recipient_name);
        $this->assertSame('https://example.test/original.png', DB::table('customer_payment_settings')->sole()->gcash_qr_image_url);
        Http::assertNothingSent();
    }

    public function test_warranty_and_financial_changes_roll_back_if_the_review_record_cannot_save(): void
    {
        DB::unprepared("CREATE TRIGGER fail_review BEFORE UPDATE ON customer_payment_submissions BEGIN SELECT RAISE(ABORT, 'test_review_failure'); END");
        try {
            $this->payments->review($this->receipt, $this->administrator, 'approved');
            $this->fail('The review trigger should reject this transaction.');
        } catch (QueryException) {
            $this->assertSame('Partial', $this->bill->fresh()->payment_status);
            $this->assertSame('Cash', $this->bill->fresh()->payment_method);
            $this->assertEquals(600.50, $this->bill->fresh()->partial_payment_amount);
            $this->assertNull($this->bill->fresh()->paid_at);
            $this->assertNull($this->appliance->fresh()->warranty_end);
            $this->assertSame('pending', $this->receipt->fresh()->status);
        }
    }

    public function test_approval_retires_the_old_checkout_link_after_the_atomic_payment_commit(): void
    {
        config(['services.paymongo.secret_key' => 'sk_test_checkout']);
        $this->bill->update(['paymongo_link_id' => 'plink_old', 'payment_url' => 'https://checkout.paymongo.test/old']);
        Http::fake(function (Request $request) {
            $bill = $this->bill->fresh();
            $this->assertSame('Paid', $bill->payment_status);
            $this->assertNull($bill->payment_url);
            $this->assertSame('approved', $this->receipt->fresh()->status);
            $this->assertSame('PATCH', $request->method());
            $this->assertTrue($request['archive']);

            return Http::response(['data' => ['id' => 'plink_old']], 200);
        });
        $this->payments->review($this->receipt, $this->administrator, 'approved');
        Http::assertSentCount(1);
        $this->assertNull($this->bill->fresh()->paymongo_link_id);
        $this->assertNull($this->bill->fresh()->payment_url);
    }

    public function test_checkout_provider_failure_cannot_discard_a_verified_payment(): void
    {
        config(['services.paymongo.secret_key' => 'sk_test_checkout']);
        $this->bill->update(['paymongo_link_id' => 'plink_old', 'payment_url' => 'https://checkout.paymongo.test/old']);
        Http::fake(['https://api.paymongo.com/*' => Http::response([], 503)]);
        $this->post(route('customer-payments.review', $this->receipt), ['decision' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame('Paid', $this->bill->fresh()->payment_status);
        $this->assertSame('approved', $this->receipt->fresh()->status);
        $this->assertNull($this->bill->fresh()->payment_url);
        $this->assertSame('plink_old', $this->bill->fresh()->paymongo_link_id);
        $this->assertSame('2027-04-09', $this->appliance->fresh()->warranty_end);
    }

    public function test_rejection_of_a_receipt_for_a_reassigned_customer_is_blocked(): void
    {
        $this->receipt->forceFill(['customer_id' => (string) Str::uuid()])->save();
        $this->post(route('customer-payments.review', $this->receipt), ['decision' => 'rejected', 'note' => 'Please correct the amount.'])
            ->assertSessionHasErrors('decision');
        $this->assertSame('pending', $this->receipt->fresh()->status);
        $this->assertSame('Partial', $this->bill->fresh()->payment_status);
    }

    public function test_archived_customer_cannot_have_a_receipt_approved(): void
    {
        $this->customer->delete();
        $this->post(route('customer-payments.review', $this->receipt), ['decision' => 'approved'])->assertSessionHasErrors('decision');
        $this->assertSame('pending', $this->receipt->fresh()->status);
        $this->assertSame('Partial', $this->bill->fresh()->payment_status);
        $this->assertNull($this->appliance->fresh()->warranty_end);
    }

    public function test_paid_shop_preference_does_not_instruct_staff_to_collect_again(): void
    {
        $this->receipt->forceFill(['method' => 'shop', 'status' => 'pay_at_shop', 'receipt_path' => null])->save();
        $this->bill->update(['payment_status' => 'Paid', 'payment_method' => 'Cash']);
        $this->get(route('customer-payments.show', $this->receipt))->assertOk()
            ->assertSee('The customer chose to pay at the shop. This transaction is already marked paid.')
            ->assertDontSee('Approve and mark paid');
    }

    public function test_missing_payment_tables_have_an_actionable_queue_and_settings_state(): void
    {
        Schema::drop('customer_payment_submissions');
        Schema::drop('customer_payment_settings');
        $this->get(route('customer-payments.index'))->assertOk()->assertSee('Payment review is not available yet');
        $this->get(route('customer-payments.settings.edit'))->assertOk();
    }
}
