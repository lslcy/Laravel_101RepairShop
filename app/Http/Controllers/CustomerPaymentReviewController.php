<?php

namespace App\Http\Controllers;

use App\Models\CustomerPaymentSubmission;
use App\Services\CustomerPaymentReviewService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerPaymentReviewController extends Controller
{
    public function __construct(private readonly CustomerPaymentReviewService $payments) {}

    private function authorizeAdmin(): void
    {
        Gate::authorize('admin-only');
        abort_unless(auth()->user()?->status === 'Active', 403);
    }

    private function reviewAvailable(): bool
    {
        return Schema::hasTable('customer_payment_submissions');
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'pending', 'approved', 'rejected', 'pay_at_shop', 'superseded'])],
            'search' => 'nullable|string|max:150',
        ]);
        $status = $filters['status'] ?? 'pending';
        $search = trim($filters['search'] ?? '');
        $paymentReviewAvailable = $this->reviewAvailable();
        $submissions = new LengthAwarePaginator([], 0, 25, 1, ['path' => $request->url()]);
        if ($paymentReviewAvailable) {
            $submissions = CustomerPaymentSubmission::with(['transaction.report', 'customer'])
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->whereHas('customer', fn ($customer) => $customer
                            ->where('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%'));
                        if (ctype_digit($search)) {
                            $query->orWhere('transaction_id', $search);
                        }
                    });
                })->orderByDesc('created_at')->orderByDesc('id')->paginate(25)->withQueryString();
        }

        return view('customer-payments.index', compact('submissions', 'status', 'search', 'paymentReviewAvailable'));
    }

    public function show(CustomerPaymentSubmission $submission)
    {
        $this->authorizeAdmin();
        $submission->load(['transaction.report.appliance', 'customer']);
        $balanceChanged = $this->payments->balanceChanged($submission);
        $storageConfigured = $this->payments->storageConfigured();
        $paymentReviewAvailable = $this->reviewAvailable();

        return view('customer-payments.show', compact('submission', 'balanceChanged', 'storageConfigured', 'paymentReviewAvailable'));
    }

    public function receipt(CustomerPaymentSubmission $submission)
    {
        $this->authorizeAdmin();
        try {
            $image = $this->payments->receipt($submission);
        } catch (ValidationException) {
            abort(503, 'The receipt image could not be loaded. Please check secure storage configuration and try again.');
        } catch (ConnectionException) {
            abort(503, 'The receipt image could not be loaded. Please try again.');
        }

        return response($image['bytes'], 200, [
            'Content-Type' => $image['mime'],
            'Content-Disposition' => 'inline; filename="gcash-receipt.'.$this->extension($image['mime']).'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    private function extension(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg', 'image/webp' => 'webp', default => 'png'
        };
    }

    public function review(Request $request, CustomerPaymentSubmission $submission)
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'note' => 'required_if:decision,rejected|nullable|string|max:1000',
            'reference_no' => 'nullable|string|max:150',
        ]);
        $this->payments->review($submission, $request->user(), $validated['decision'], $validated['note'] ?? null, $validated['reference_no'] ?? null);

        return redirect()->route('customer-payments.show', $submission)
            ->with('success', $validated['decision'] === 'approved'
                ? 'Receipt approved. The payment is now paid.'
                : 'Receipt rejected. The customer can submit a corrected screenshot.');
    }

    public function editSettings()
    {
        $this->authorizeAdmin();
        $paymentReviewAvailable = Schema::hasTable('customer_payment_settings');
        $settings = $paymentReviewAvailable ? DB::table('customer_payment_settings')->where('id', 1)->first() : null;
        $storageConfigured = $this->payments->storageConfigured();
        $defaultRecipient = 'L. C. O.';

        return view('customer-payments.settings', compact('settings', 'storageConfigured', 'defaultRecipient', 'paymentReviewAvailable'));
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeAdmin();
        abort_unless(Schema::hasTable('customer_payment_settings'), 503, 'GCash settings are not available yet.');
        $existing = DB::table('customer_payment_settings')->where('id', 1)->exists();
        $validated = $request->validate([
            'gcash_recipient_name' => 'required|string|max:150',
            'qr_image' => [Rule::requiredIf(! $existing), 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        try {
            $this->payments->saveSettings($validated['gcash_recipient_name'], $request->file('qr_image'));
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['qr_image' => 'The QR image upload timed out. Please try again.']);
        }

        return redirect()->route('customer-payments.settings.edit')->with('success', 'GCash payment settings updated.');
    }
}
