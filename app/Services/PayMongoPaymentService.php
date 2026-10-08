<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PayMongoPaymentService
{
    private const API_URL = 'https://api.paymongo.com/v1';

    /** Call after the bill has committed; provider outages must not discard it. */
    public function ensurePaymentLink(Transaction $transaction, bool $amountChanged = false): bool
    {
        try {
            if (! $transaction->exists) {
                return false;
            }

            $transaction->refresh();
            $amount = $this->amountInCentavos($transaction);
            $minimum = max(100, (int) round((float) config('services.paymongo.minimum_amount', 100) * 100));
            $report = $transaction->report_id ? $transaction->report()->withTrashed()->first() : null;
            $reportClosed = $transaction->report_id
                && (! $report || $report->trashed() || $report->status === 'Cancelled');
            $eligible = ! $transaction->trashed() && ! $reportClosed && $transaction->payment_status !== 'Paid'
                && $amount >= $minimum && $amount <= 999999999;

            if ($eligible && ! $amountChanged && $transaction->paymongo_link_id && $transaction->payment_url) {
                return true;
            }

            // A retained ID with no URL means retirement failed previously. Retry it
            // before creating another payable link, even if this bill did not change.
            if ($transaction->paymongo_link_id && ! $this->retirePaymentLink($transaction)) {
                return false;
            }

            if (! $eligible || $this->secretKey() === '') {
                return false;
            }

            $response = $this->client()->post(self::API_URL.'/payment_links', [
                'amount' => $amount,
                'currency' => 'PHP',
                'description' => $transaction->report_id
                    ? 'Repair Service Payment for Report #'.$transaction->report_id
                    : 'Repair Service Payment for Transaction #'.$transaction->id,
                'remarks' => 'Transaction #'.$transaction->id,
                'metadata' => [
                    'transaction_id' => (string) $transaction->id,
                    'report_id' => (string) ($transaction->report_id ?? ''),
                ],
                'restriction' => ['completed_sessions' => ['limit' => 1]],
            ]);

            if (! $response->successful()) {
                $this->logFailure('create', $transaction, $response->status());

                return false;
            }

            $data = $response->json('data');
            $linkId = is_array($data) ? ($data['id'] ?? null) : null;
            $url = is_array($data) ? ($data['url'] ?? data_get($data, 'attributes.checkout_url')) : null;

            if (! is_string($linkId) || $linkId === '' || ! is_string($url)
                || ! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
                $this->logFailure('create_invalid_response', $transaction);

                return false;
            }

            $transaction->update(['paymongo_link_id' => $linkId, 'payment_url' => $url]);

            return true;
        } catch (Throwable $exception) {
            $this->logFailure('provision', $transaction, exception: $exception);

            return false;
        }
    }

    /** Disable the app URL immediately and keep the old ID until PayMongo archives it. */
    public function retirePaymentLink(Transaction $transaction): bool
    {
        try {
            if ($transaction->payment_url !== null) {
                $transaction->update(['payment_url' => null]);
            }

            if (! $transaction->paymongo_link_id) {
                return true;
            }

            if ($this->secretKey() === '') {
                return false;
            }

            $id = rawurlencode($transaction->paymongo_link_id);
            $response = $this->client()->patch(self::API_URL.'/payment_links/'.$id, ['archive' => true]);

            // Historical /v1/links use the same stored link ID but a different
            // archive endpoint. Only a not-found response triggers this fallback.
            if ($response->status() === 404) {
                $response = $this->client()->post(self::API_URL.'/links/'.$id.'/archive');
            }

            if (! $response->successful() && $response->status() !== 404) {
                $this->logFailure('archive', $transaction, $response->status());

                return false;
            }

            $transaction->update(['paymongo_link_id' => null, 'payment_url' => null]);

            return true;
        } catch (Throwable $exception) {
            $this->logFailure('archive', $transaction, exception: $exception);

            return false;
        }
    }

    public function amountInCentavos(Transaction $transaction): int
    {
        $total = (int) round((float) $transaction->total_amount * 100);
        $partial = $transaction->payment_status === 'Partial'
            ? (int) round((float) $transaction->partial_payment_amount * 100)
            : 0;

        return max(0, $total - $partial);
    }

    private function secretKey(): string
    {
        return trim((string) config('services.paymongo.secret_key', ''));
    }

    private function client(): PendingRequest
    {
        return Http::withBasicAuth($this->secretKey(), '')
            ->acceptJson()->asJson()->connectTimeout(3)
            ->timeout(max(1, (int) config('services.paymongo.timeout', 10)));
    }

    private function logFailure(string $operation, Transaction $transaction, ?int $httpStatus = null, ?Throwable $exception = null): void
    {
        // Do not log secret keys, provider response bodies, or customer payment data.
        Log::warning('PayMongo payment link operation failed.', [
            'operation' => $operation,
            'transaction_id' => $transaction->id,
            'http_status' => $httpStatus,
            'exception_type' => $exception ? get_class($exception) : null,
        ]);
    }
}
