<x-app-layout>
    @php
        $transaction = $submission->transaction;
        $isPendingReceipt = $submission->status === 'pending' && $submission->method === 'gcash';
        $transactionPaid = strtolower(trim((string) $transaction?->payment_status)) === 'paid';
        $transactionArchived = !$transaction || $transaction->trashed();
        $customerArchived = !$submission->customer || $submission->customer->trashed();
        $canApprove = $isPendingReceipt && !$transactionPaid && !$transactionArchived && !$customerArchived && !$balanceChanged && $storageConfigured;
        $statusLabels = ['pending' => 'Pending review', 'pay_at_shop' => 'Pay at the shop', 'approved' => 'Approved', 'rejected' => 'Rejected', 'superseded' => 'Superseded'];
        $statusTones = ['pending' => 'warning', 'pay_at_shop' => 'info', 'approved' => 'success', 'rejected' => 'danger', 'superseded' => 'neutral'];
        $customerName = trim(($submission->customer?->first_name ?? '') . ' ' . ($submission->customer?->last_name ?? '')) ?: 'Customer unavailable';
        $partialAmount = in_array(strtolower(trim((string) $transaction?->payment_status)), ['partial', 'partially paid', 'partial payment']) ? (float) ($transaction?->partial_payment_amount ?? 0) : 0;
        $balance = $transactionPaid ? 0 : ($transaction?->total_amount === null ? null : max(0, (float) $transaction->total_amount - $partialAmount));
    @endphp
    <div class="ui-page" x-data="{ reviewing: null }">
        <div class="ui-page-header">
            <div>
                <h1 class="ui-page-title">Payment for transaction #{{ $submission->transaction_id }}</h1>
                <p class="ui-page-subtitle">{{ $customerName }}</p>
            </div>
            <a href="{{ route('customer-payments.index') }}" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" /></svg>
                Back to payments
            </a>
        </div>

        @include('customer-payments.validation-errors')

        <div class="grid min-w-0 gap-6 lg:grid-cols-2">
            <section class="ui-card min-w-0 p-5 sm:p-6" aria-labelledby="receipt-heading">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="receipt-heading" class="text-lg font-semibold">{{ $submission->method === 'gcash' ? 'Customer receipt' : 'Payment preference' }}</h2>
                    <x-status-badge :status="$statusLabels[$submission->status] ?? 'Unknown'" :tone="$statusTones[$submission->status] ?? 'neutral'" />
                </div>
                @if($submission->receipt_path && $storageConfigured)
                    <div class="mt-5 overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-slate-600">
                        <img src="{{ route('customer-payments.receipt', $submission) }}" alt="GCash receipt uploaded for transaction {{ $submission->transaction_id }}" class="max-h-[640px] w-full object-contain" loading="lazy">
                    </div>
                    <a href="{{ route('customer-payments.receipt', $submission) }}" target="_blank" rel="noopener" class="mt-3 inline-flex min-h-[48px] items-center gap-2 font-medium text-blue-700 underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-300">
                        Open receipt at full size <span class="sr-only">in a new tab</span>
                        <svg class="h-4 w-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7v7m0-7L10 14M7 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2" /></svg>
                    </a>
                    <p class="mt-3 text-sm text-gray-600 dark:text-slate-300">Check the amount, recipient, reference number, and your GCash records before approving.</p>
                @elseif($submission->receipt_path)
                    <div role="status" class="mt-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">Receipt access is not configured. Contact the system administrator before approving this payment.</div>
                @else
                    <p class="mt-5 text-sm text-gray-600 dark:text-slate-300">{{ $transactionPaid ? 'The customer chose to pay at the shop. This transaction is already marked paid.' : 'The customer chose to pay at the shop. No online payment has been recorded. Receive and record the payment through the existing transaction screen.' }}</p>
                @endif
            </section>

            <div class="min-w-0 space-y-6">
                <section class="ui-card p-5 sm:p-6" aria-labelledby="payment-summary-heading">
                    <h2 id="payment-summary-heading" class="text-lg font-semibold">Payment details</h2>
                    <dl class="mt-5 space-y-4 text-sm">
                        <div class="flex flex-wrap justify-between gap-2"><dt class="text-gray-600 dark:text-slate-300">Submitted amount</dt><dd class="font-semibold tabular-nums">₱{{ number_format((float) $submission->amount, 2) }}</dd></div>
                        <div class="flex flex-wrap justify-between gap-2"><dt class="text-gray-600 dark:text-slate-300">Current balance</dt><dd class="font-semibold tabular-nums">{{ $balance === null ? 'Not available' : '₱' . number_format($balance, 2) }}</dd></div>
                        <div class="flex flex-wrap justify-between gap-2"><dt class="text-gray-600 dark:text-slate-300">Recorded payment status</dt><dd><x-status-badge :status="$transaction?->payment_status ?? 'Unavailable'" /></dd></div>
                        <div class="flex flex-wrap justify-between gap-2"><dt class="text-gray-600 dark:text-slate-300">Method</dt><dd class="font-medium">{{ $submission->method === 'gcash' ? 'GCash' : 'Pay at the shop' }}</dd></div>
                        <div class="flex flex-wrap justify-between gap-2"><dt class="text-gray-600 dark:text-slate-300">Submitted</dt><dd>{{ $submission->created_at?->timezone('Asia/Manila')->format('M d, Y g:i A') ?? 'Date unavailable' }}</dd></div>
                        @if($submission->reviewed_at)
                            <div class="flex flex-wrap justify-between gap-2"><dt class="text-gray-600 dark:text-slate-300">Reviewed</dt><dd>{{ $submission->reviewed_at->timezone('Asia/Manila')->format('M d, Y g:i A') }}</dd></div>
                        @endif
                    </dl>
                    @if($submission->review_note)
                        <div class="mt-5 rounded-lg bg-gray-50 p-4 dark:bg-slate-700/50"><h3 class="text-sm font-semibold">Review note</h3><p class="mt-2 whitespace-pre-line break-words text-sm text-gray-700 dark:text-slate-200">{{ $submission->review_note }}</p></div>
                    @endif
                    @if($transaction && !$transaction->trashed())
                        <a href="{{ route('transactions.show', $transaction) }}" class="mt-4 inline-flex min-h-[48px] items-center font-medium text-blue-700 underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-300">View transaction</a>
                    @endif
                </section>

                @if($isPendingReceipt)
                    @if($balanceChanged || $transactionPaid || $transactionArchived || $customerArchived)
                        <div role="status" class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                            {{ $transactionPaid ? 'This transaction is already paid. Do not record a second payment.' : ($transactionArchived ? 'This transaction is archived or unavailable. It cannot be marked paid here.' : ($customerArchived ? 'This customer account is archived or unavailable. Payment cannot be approved here.' : 'The balance changed after this receipt was submitted. Reject it with an explanation before requesting a corrected payment.')) }}
                        </div>
                    @endif
                    <section class="ui-card p-5 sm:p-6" aria-labelledby="approve-heading">
                        <h2 id="approve-heading" class="text-lg font-semibold">Approve payment</h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-slate-300">Approval records the outstanding balance as paid. Only approve after you verify that the money was received.</p>
                        <form method="POST" action="{{ route('customer-payments.review', $submission) }}" class="mt-5 space-y-4" @submit="reviewing = 'approved'">
                            @csrf
                            <input type="hidden" name="decision" value="approved">
                            <div>
                                <label for="approval-reference" class="block text-sm font-medium">GCash reference number <span class="font-normal text-gray-500 dark:text-slate-400">(optional)</span></label>
                                <input id="approval-reference" name="reference_no" maxlength="150" value="{{ old('reference_no') }}" class="mt-2 block min-h-[48px] w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-600" @disabled(!$canApprove)>
                            </div>
                            <button type="submit" @disabled(!$canApprove) :disabled="reviewing !== null || {{ $canApprove ? 'false' : 'true' }}" class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus-visible:ring-offset-slate-900">
                                <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" /></svg>
                                <span x-text="reviewing === 'approved' ? 'Approving payment...' : 'Approve and mark paid'">Approve and mark paid</span>
                            </button>
                        </form>
                    </section>
                    <section class="ui-card p-5 sm:p-6" aria-labelledby="reject-heading">
                        <h2 id="reject-heading" class="text-lg font-semibold">Request a correction</h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-slate-300">Explain what the customer needs to correct. The payment will remain unpaid.</p>
                        <form method="POST" action="{{ route('customer-payments.review', $submission) }}" class="mt-5 space-y-4" @submit="reviewing = 'rejected'">
                            @csrf
                            <input type="hidden" name="decision" value="rejected">
                            <div>
                                <label for="rejection-note" class="block text-sm font-medium">Reason for rejection <span aria-hidden="true">*</span></label>
                                <textarea id="rejection-note" name="note" required maxlength="1000" rows="3" aria-describedby="rejection-help" class="mt-2 block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-600">{{ old('decision') === 'rejected' ? old('note') : '' }}</textarea>
                                <p id="rejection-help" class="mt-2 text-sm text-gray-500 dark:text-slate-400">This explanation appears in the customer's app.</p>
                            </div>
                            <button type="submit" :disabled="reviewing !== null" class="inline-flex min-h-[48px] w-full items-center justify-center rounded-lg border border-red-300 px-5 py-3 text-sm font-semibold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-950">
                                <span x-text="reviewing === 'rejected' ? 'Rejecting receipt...' : 'Reject receipt'">Reject receipt</span>
                            </button>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>