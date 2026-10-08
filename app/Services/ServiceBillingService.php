<?php

namespace App\Services;

use App\Models\ServiceDetail;
use App\Models\ServiceReport;
use App\Models\Transaction;

class ServiceBillingService
{
    /**
     * Called inside the report's database transaction after its costs are saved.
     *
     * @return array{0: ?Transaction, 1: bool}
     */
    public function syncPendingBill(ServiceReport $report, ServiceDetail $details): array
    {
        $transactions = $report->transactions()->withTrashed()
            ->latest()->orderByDesc('id')->lockForUpdate()->get();

        // Receipts and partially paid bills retain their original financial amounts.
        if ($transactions->contains(fn ($transaction) => in_array($transaction->payment_status, ['Paid', 'Partial'], true))) {
            return [null, false];
        }

        $bill = $transactions->first(fn ($transaction) => !$transaction->trashed()
            && in_array($transaction->payment_status, ['Pending', 'Unpaid'], true));

        // Archiving a bill is deliberate; editing its report must not recreate it.
        if (!$bill && $transactions->isNotEmpty()) {
            return [null, false];
        }

        $bill ??= new Transaction([
            'report_id' => $report->id,
            'payment_status' => 'Pending',
            'received_by' => 'System',
        ]);

        $previousAmount = $bill->exists ? (float) $bill->total_amount : null;
        $bill->fill([
            'customer_id' => $report->customer_id,
            'labor_total' => $details->labor ?? 0,
            'parts_total' => $details->parts_total_charge ?? 0,
            'total_amount' => $details->total_amount ?? 0,
        ]);
        $amountChanged = $previousAmount !== null
            && (int) round($previousAmount * 100) !== (int) round((float) $bill->total_amount * 100);
        $bill->save();

        return [$bill, $amountChanged];
    }
}
