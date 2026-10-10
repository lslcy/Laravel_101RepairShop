<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPaymentSubmission;
use App\Models\ServiceReport;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerPaymentReviewService
{
    public const QR_BUCKET = 'shop-payment-qr';

    public const RECEIPT_BUCKET = 'payment-receipts';

    public const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    public function review(
        CustomerPaymentSubmission $submission,
        User $administrator,
        string $decision,
        ?string $note = null,
        ?string $referenceNo = null,
    ): CustomerPaymentSubmission {
        Gate::forUser($administrator)->authorize('admin-only');
        abort_unless($administrator->status === 'Active' && ! $administrator->trashed(), 403);
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => 'Choose approve or reject.']);
        }
        $note = trim($note ?? '');
        $referenceNo = trim($referenceNo ?? '');
        if ($decision === 'rejected' && $note === '') {
            throw ValidationException::withMessages(['note' => 'Add a reason so the customer can correct the receipt.']);
        }
        if (mb_strlen($note) > 1000 || mb_strlen($referenceNo) > 150) {
            throw ValidationException::withMessages(['decision' => 'The review note or reference number is too long.']);
        }

        $reviewed = DB::transaction(function () use ($submission, $administrator, $decision, $note, $referenceNo) {
            // Match the existing billing controller's report -> bill lock order.
            $bill = Transaction::withTrashed()->findOrFail($submission->transaction_id);
            if ($bill->report_id) {
                ServiceReport::withTrashed()->whereKey($bill->report_id)->lockForUpdate()->first();
            }
            $bill = Transaction::withTrashed()->whereKey($bill->id)->lockForUpdate()->firstOrFail();
            $receipt = CustomerPaymentSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if ($receipt->status !== 'pending' || $receipt->method !== 'gcash') {
                throw ValidationException::withMessages(['decision' => 'Only a pending GCash receipt can be reviewed.']);
            }
            if ((int) $receipt->transaction_id !== (int) $bill->id || (string) $receipt->customer_id !== (string) $bill->customer_id) {
                throw ValidationException::withMessages(['decision' => 'This receipt no longer belongs to the current bill.']);
            }

            $reviewedAt = now();
            $identity = trim($administrator->full_name) ?: $administrator->username;
            if ($decision === 'approved') {
                if ($bill->trashed() || $this->isPaid($bill)) {
                    throw ValidationException::withMessages(['decision' => 'This transaction is archived or already paid.']);
                }
                if (! Customer::whereKey($bill->customer_id)->exists()) {
                    throw ValidationException::withMessages(['decision' => 'The customer account is archived.']);
                }
                $balance = $this->remainingCentavos($bill);
                if ($balance === null || $balance <= 0 || $balance !== $this->centavos($receipt->amount)) {
                    throw ValidationException::withMessages(['decision' => 'The balance changed. Reject this receipt and request a new one before approving payment.']);
                }
                $this->receiptPath($receipt);
                // Query builder preserves this timestamp and Philippine calendar date
                // without a model event rewriting one from the other.
                DB::table('transactions')->where('id', $bill->id)->update([
                    'payment_status' => 'Paid',
                    'payment_method' => 'GCash',
                    'partial_payment_amount' => null,
                    'paid_at' => $reviewedAt,
                    'payment_date' => $reviewedAt->copy()->timezone('Asia/Manila')->toDateString(),
                    'reference_no' => $referenceNo !== '' ? $referenceNo : $bill->reference_no,
                    'received_by' => $identity,
                    'payment_url' => null,
                    'updated_at' => $reviewedAt,
                ]);
                $appliance = $bill->report?->appliance;
                $months = match ($appliance?->appliance_size) {
                    'Small' => 1,
                    'Medium' => 3,
                    'Large' => 6,
                    default => 0,
                };
                if ($months > 0) {
                    $appliance->update(['warranty_end' => $reviewedAt->copy()->addMonths($months)->toDateString()]);
                }
            }
            $receipt->forceFill([
                'status' => $decision,
                'reviewed_at' => $reviewedAt,
                'reviewed_by' => 'Administrator #'.$administrator->id.' '.$identity,
                'review_note' => $note !== '' ? $note : null,
            ])->save();

            return $receipt;
        }, 3);
        if ($decision === 'approved') {
            // Provider outages never roll back a manually verified payment.
            if ($bill = Transaction::find($reviewed->transaction_id)) {
                app(PayMongoPaymentService::class)->ensurePaymentLink($bill);
            }
        }

        return $reviewed;
    }

    public function isPaid(Transaction $transaction): bool
    {
        return strtolower(trim((string) $transaction->payment_status)) === 'paid';
    }

    public function remainingCentavos(Transaction $transaction): ?int
    {
        if ($transaction->total_amount === null) {
            return null;
        }
        if ($this->isPaid($transaction)) {
            return 0;
        }
        $paid = in_array(strtolower(trim((string) $transaction->payment_status)), ['partial', 'partially paid', 'partial payment'], true)
            ? $this->centavos($transaction->partial_payment_amount ?? 0) : 0;

        return max(0, $this->centavos($transaction->total_amount) - $paid);
    }

    private function centavos(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    public function balanceChanged(CustomerPaymentSubmission $submission): bool
    {
        $bill = $submission->transaction;

        return ! $bill || $this->remainingCentavos($bill) !== $this->centavos($submission->amount);
    }

    public function storageConfigured(): bool
    {
        $url = trim((string) config('services.supabase.url'));
        $key = trim((string) config('services.supabase.service_key'));

        return $key !== '' && filter_var($url, FILTER_VALIDATE_URL)
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && ! parse_url($url, PHP_URL_USER);
    }

    private function storageClient(): PendingRequest
    {
        if (! $this->storageConfigured()) {
            throw ValidationException::withMessages(['qr_image' => 'Secure payment image storage is not configured. Ask the project administrator to configure the server.']);
        }

        return Http::baseUrl(rtrim(config('services.supabase.url'), '/').'/storage/v1')
            ->withToken(config('services.supabase.service_key'))
            ->withHeaders(['apikey' => config('services.supabase.service_key')])
            ->connectTimeout(5)->timeout(max(1, (int) config('services.supabase.storage_timeout', 15)))->withoutRedirecting();
    }

    private function receiptPath(CustomerPaymentSubmission $submission): string
    {
        $prefix = preg_quote($submission->auth_id.'/'.$submission->transaction_id.'/'.$submission->id, '~');
        $path = (string) $submission->receipt_path;
        if ($submission->method !== 'gcash' || ! preg_match('~\A'.$prefix.'\.(jpg|png|webp)\z~D', $path)) {
            throw ValidationException::withMessages(['decision' => 'This receipt path is invalid. Reject it and request a new screenshot.']);
        }

        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    /** The bytes are returned to an authenticated admin response, never a public URL. */
    public function receipt(CustomerPaymentSubmission $submission): array
    {
        $path = $this->receiptPath($submission);
        $response = $this->storageClient()->get('/object/authenticated/'.self::RECEIPT_BUCKET.'/'.$path);
        if (! $response->successful() || strlen($response->body()) > self::MAX_IMAGE_BYTES) {
            throw ValidationException::withMessages(['decision' => 'The receipt image could not be loaded. Please try again.']);
        }
        $bytes = $response->body();
        $image = @getimagesizefromstring($bytes);
        $mime = $image['mime'] ?? null;
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw ValidationException::withMessages(['decision' => 'This receipt is not a supported image.']);
        }

        return ['bytes' => $bytes, 'mime' => $mime];
    }

    public function saveSettings(string $recipient, ?UploadedFile $image): void
    {
        $recipient = trim($recipient);
        if ($recipient === '' || mb_strlen($recipient) > 150) {
            throw ValidationException::withMessages(['gcash_recipient_name' => 'Enter the GCash recipient name, up to 150 characters.']);
        }
        $existing = DB::table('customer_payment_settings')->where('id', 1)->first();
        $url = $existing?->gcash_qr_image_url;
        if ($image) {
            $mime = $image->getMimeType();
            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || $image->getSize() > self::MAX_IMAGE_BYTES) {
                throw ValidationException::withMessages(['qr_image' => 'Choose a JPG, PNG, or WebP QR image smaller than 5 MB.']);
            }
            $extension = match ($mime) {
                'image/jpeg' => 'jpg', 'image/webp' => 'webp', default => 'png'
            };
            $path = 'gcash/'.Str::uuid().'.'.$extension;
            $response = $this->storageClient()->withHeaders(['x-upsert' => 'false'])
                ->withBody(file_get_contents($image->getRealPath()), $mime)
                ->post('/object/'.self::QR_BUCKET.'/'.$path);
            if (! $response->successful()) {
                throw ValidationException::withMessages(['qr_image' => 'The QR image could not be uploaded. Please try again.']);
            }
            $url = rtrim(config('services.supabase.url'), '/').'/storage/v1/object/public/'.self::QR_BUCKET.'/'.$path;
        }
        if (! $url) {
            throw ValidationException::withMessages(['qr_image' => 'Upload the shop GCash QR image before saving.']);
        }
        DB::table('customer_payment_settings')->updateOrInsert(['id' => 1], [
            'gcash_qr_image_url' => $url,
            'gcash_recipient_name' => $recipient,
        ]);
    }
}
