<x-app-layout>
    <div class="ui-page">
        <!-- Header -->
        <div class="ui-page-header">
            <h2 class="ui-page-title">Transaction #{{ $transaction->id }}</h2>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('transactions.index') }}"
                    class="px-4 py-2 border border-gray-300 dark:border-slate-500 rounded-lg text-sm font-medium text-gray-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:bg-slate-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    Back to List
                </a>
                <a href="{{ route('transactions.edit', $transaction) }}"
                    class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    Edit Transaction
                </a>
            </div>
        </div>

        @can('admin-only')
            @if($pendingSubmission ?? null)
                <section class="ui-card border-amber-300 p-5 dark:border-amber-700" aria-labelledby="pending-receipt-heading">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 id="pending-receipt-heading" class="font-semibold">GCash receipt pending review</h2>
                            <p class="mt-2 text-sm text-gray-600 dark:text-slate-300">The customer submitted a receipt for ₱{{ number_format((float) $pendingSubmission->amount, 2) }}. Verify it before recording this balance as paid.</p>
                        </div>
                        <a href="{{ route('customer-payments.show', $pendingSubmission) }}" class="inline-flex min-h-[48px] shrink-0 items-center justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900">Review receipt</a>
                    </div>
                </section>
            @elseif(isset($paymentReviewAvailable) && !$paymentReviewAvailable)
                <div role="status" class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">Customer receipt review has not been set up yet. This transaction can still be managed here.</div>
            @endif
        @endcan

        <!-- Details Card -->
        <div class="ui-card overflow-hidden">
            <div class="p-6">
                <!-- Status Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-slate-400">Date Computed</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $transaction->created_at->format('M d, Y g:i A') }}
                        </p>
                    </div>
                    <div class="md:text-right">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-slate-400">Payment Status</h3>
                        <x-status-badge :status="$transaction->payment_status" class="mt-1" />
                    </div>
                </div>

                <!-- Additional Payment Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-8 mt-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-slate-400">Received By</h3>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $transaction->received_by ?? 'System' }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-slate-400">Payment Method</h3>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $transaction->payment_method ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-slate-400">Reference Number</h3>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $transaction->reference_no ?? 'N/A' }}</p>
                    </div>
                    @if($transaction->payment_status === 'Partial')
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-slate-400">Partial Payment Amount</h3>
                        <p class="mt-1 text-sm font-semibold text-yellow-600 dark:text-yellow-400">₱{{ number_format($transaction->partial_payment_amount, 2) }}</p>
                    </div>
                    @endif
                </div>

                <hr class="border-gray-100 dark:border-slate-700 mb-8">

                <!-- Linked Service Report -->
                @if($transaction->report)
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Linked Service Report</h3>
                        <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-4 border border-gray-200 dark:border-slate-600">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ $transaction->report->customer->first_name ?? '' }}
                                        {{ $transaction->report->customer->last_name ?? '' }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">
                                        {{ $transaction->report->appliance_name }}
                                        ({{ $transaction->report->brand_model }})
                                    </p>
                                </div>
                                <a href="{{ route('services.show', $transaction->report) }}"
                                    class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 flex items-center transition-colors shadow-sm bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 px-3 py-2 rounded-lg hover:bg-gray-50 dark:bg-slate-700/50">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                    View Report
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Total Amount Banner -->
                <div class="bg-green-50 rounded-lg p-6 text-center border border-green-100 mb-8">
                    <p class="text-sm font-medium text-green-800 uppercase tracking-wide">Total Amount Due</p>
                    <h2 class="mt-2 text-4xl font-extrabold text-green-600">
                        ₱{{ number_format($transaction->total_amount, 2) }}
                    </h2>
                    @if($transaction->payment_date)
                        <p class="mt-2 text-sm text-green-700">
                            Paid on: {{ \Carbon\Carbon::parse($transaction->payment_date)->format('M d, Y g:i A') }}
                        </p>
                    @endif
                </div>

                <!-- PayMongo Link Section -->
                @if($transaction->payment_url && $transaction->payment_status !== 'Paid')
                    <hr class="border-gray-100 dark:border-slate-700 mb-8">
                    <div class="max-w-xl mx-auto text-center" x-data="{ copied: false }">
                        <div class="inline-flex items-center justify-center p-3 bg-blue-50 rounded-full mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">PayMongo Payment Link</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-slate-400 mb-4">
                            Send this link to the customer so they can securely pay online.
                        </p>

                        <div class="flex shadow-sm rounded-md">
                            <div class="relative flex-grow focus-within:z-10">
                                <input type="text" id="payment_url" readonly value="{{ $transaction->payment_url }}"
                                    class="block w-full rounded-none rounded-l-md border-gray-300 bg-gray-50 dark:bg-slate-700/50 text-gray-500 dark:text-slate-400 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                            <button type="button" @click="
                                        navigator.clipboard.writeText(document.getElementById('payment_url').value); 
                                        copied = true; 
                                        setTimeout(() => copied = false, 2000);
                                    "
                                class="relative -ml-px justify-center inline-flex items-center space-x-2 border border-gray-300 bg-gray-50 dark:bg-slate-700/50 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:bg-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors w-32">
                                <template x-if="!copied">
                                    <span class="flex items-center">
                                        <svg class="mr-2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3">
                                            </path>
                                        </svg>
                                        Copy Link
                                    </span>
                                </template>
                                <template x-if="copied">
                                    <span class="flex items-center text-green-600">
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Copied!
                                    </span>
                                </template>
                            </button>
                            <a href="{{ $transaction->payment_url }}" target="_blank"
                                class="relative -ml-px inline-flex items-center rounded-r-md border border-gray-300 dark:border-slate-500 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
                                Open
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                                    </path>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
