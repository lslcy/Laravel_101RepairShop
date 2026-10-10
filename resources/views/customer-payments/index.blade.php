<x-app-layout>
    <div class="ui-page">
        <div class="ui-page-header">
            <div>
                <h1 class="ui-page-title">Customer payments</h1>
            </div>
            <a href="{{ route('customer-payments.settings.edit') }}"
                class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v6h-6v-2" /></svg>
                GCash QR settings
            </a>
        </div>

        @if(!$paymentReviewAvailable)
            <div role="status" class="ui-card border-amber-300 p-6 dark:border-amber-700">
                <h2 class="font-semibold">Payment review is not available yet</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-slate-300">Customer payment submissions have not been set up. Contact the system administrator to finish setup. Existing transactions are still available.</p>
                <a href="{{ route('transactions.index') }}" class="mt-4 inline-flex min-h-[48px] items-center font-medium text-blue-700 underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-300">View transactions</a>
            </div>
        @else
            @php
                $statusLabels = ['pending' => 'Pending review', 'pay_at_shop' => 'Pay at the shop', 'approved' => 'Approved', 'rejected' => 'Rejected', 'superseded' => 'Superseded'];
                $statusTones = ['pending' => 'warning', 'pay_at_shop' => 'info', 'approved' => 'success', 'rejected' => 'danger', 'superseded' => 'neutral'];
            @endphp
            <div class="ui-card p-4">
                <form method="GET" action="{{ route('customer-payments.index') }}" class="flex flex-col gap-4 md:flex-row md:items-end">
                    <div class="min-w-0 flex-1">
                        <label for="payment-search" class="block text-sm font-medium">Search payments</label>
                        <input id="payment-search" name="search" value="{{ $search }}" type="search" maxlength="100" placeholder="Customer name or transaction number"
                            class="mt-2 block min-h-[48px] w-full rounded-lg border-gray-300 bg-white px-3 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800">
                    </div>
                    <div>
                        <label for="payment-status" class="block text-sm font-medium">Submission status</label>
                        <select id="payment-status" name="status" class="mt-2 block min-h-[48px] w-full rounded-lg border-gray-300 bg-white px-3 pr-9 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800">
                            <option value="all" @selected($status === 'all')>All submissions</option>
                            @foreach($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="inline-flex min-h-[48px] items-center justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900">Apply filters</button>
                    @if($search !== '' || $status !== 'pending')
                        <a href="{{ route('customer-payments.index') }}" class="inline-flex min-h-[48px] items-center justify-center rounded-lg px-4 py-3 text-sm font-medium text-blue-700 hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-300 dark:hover:bg-slate-700">Reset filters</a>
                    @endif
                </form>
            </div>

            <div class="ui-card overflow-hidden">
                @forelse($submissions as $submission)
                    @php
                        $customerName = trim(($submission->customer?->first_name ?? '') . ' ' . ($submission->customer?->last_name ?? '')) ?: 'Customer unavailable';
                        $label = $statusLabels[$submission->status] ?? 'Unknown';
                        $tone = $statusTones[$submission->status] ?? 'neutral';
                    @endphp
                    <article class="flex flex-col gap-4 border-b border-gray-200 p-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700">
                        <div class="min-w-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="font-semibold">Transaction #{{ $submission->transaction_id }}</h2>
                                <x-status-badge :status="$label" :tone="$tone" />
                            </div>
                            <p class="break-words text-sm text-gray-700 dark:text-slate-200">{{ $customerName }}</p>
                            <p class="text-sm text-gray-600 dark:text-slate-300">{{ $submission->method === 'gcash' ? 'GCash receipt' : 'Pay at the shop' }} &middot; ₱{{ number_format((float) $submission->amount, 2) }}</p>
                            <p class="text-sm text-gray-500 dark:text-slate-400">Submitted {{ $submission->created_at?->timezone('Asia/Manila')->format('M d, Y g:i A') ?? 'Date unavailable' }}</p>
                        </div>
                        <a href="{{ route('customer-payments.show', $submission) }}" aria-label="{{ $submission->status === 'pending' ? 'Review receipt' : 'View payment submission' }} for transaction {{ $submission->transaction_id }}"
                            class="inline-flex min-h-[48px] shrink-0 items-center justify-center gap-2 rounded-lg border border-blue-300 px-4 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-blue-600 dark:text-blue-300 dark:hover:bg-slate-700">
                            {{ $submission->status === 'pending' ? 'Review receipt' : 'View submission' }}
                            <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" /></svg>
                        </a>
                    </article>
                @empty
                    <div class="px-6 py-14 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h4M7 3h10a2 2 0 0 1 2 2v16l-3-2-4 2-4-2-3 2V5a2 2 0 0 1 2-2Z" /></svg>
                        <h2 class="mt-4 text-lg font-semibold">{{ $status === 'pending' && $search === '' ? 'No receipts waiting for review' : 'No matching submissions' }}</h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-slate-300">{{ $status === 'pending' && $search === '' ? 'New customer receipts will appear here when they confirm a GCash payment.' : 'Try another customer name, transaction number, or status.' }}</p>
                    </div>
                @endforelse
                @if($submissions->hasPages())
                    <div class="ui-pagination">{{ $submissions->links() }}</div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>