<x-app-layout>
    <style>
        @media print {
            @page {
                size: landscape;
                margin: 0;
            }
        }
    </style>
    <div class="space-y-6 print:p-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Transactions</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">View and manage financial transactions</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="window.print()"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-slate-500 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-5 h-5 mr-2 -ml-1 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                        </path>
                    </svg>
                    Print List
                </button>
                @if(in_array(auth()->user()->role, ['Administrator', 'Secretary']))
                    <a href="{{ route('transactions.create') }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 dark:bg-blue-900 dark:hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        New Transaction
                    </a>
                @endif
            </div>
        </div>

        <!-- Filters & Search (Always Visible) -->
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-gray-100 dark:border-slate-700 shadow-sm print:hidden">
            <form method="GET" action="{{ route('transactions.index') }}" class="flex flex-col md:flex-row gap-4" id="filterForm">
                
                <!-- Search -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="block w-full pl-10 pr-10 py-2 border border-gray-200 dark:border-slate-600 rounded-lg leading-5 bg-white dark:bg-slate-800 placeholder-gray-400 focus:outline-none focus:placeholder-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                        placeholder="Search ID or Customer Name..." oninput="this.form.submit()">
                    
                    @if(request('search'))
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <button type="button" onclick="document.querySelector('input[name=search]').value=''; document.getElementById('filterForm').submit();" class="text-gray-400 hover:text-red-500 focus:outline-none transition-colors" title="Clear Search">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Filters -->
                <div class="flex flex-col xl:flex-row gap-4">
                    <!-- Date Filter -->
                    <input type="date" name="date" value="{{ request('date') }}"
                        onchange="this.form.submit()"
                        class="block w-full xl:w-auto py-2 px-3 border border-gray-200 dark:border-slate-600 rounded-lg leading-5 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                        title="Filter by Payment or Due Date">

                    <!-- Status Filter -->
                    <select name="status" onchange="this.form.submit()"
                        class="block w-full xl:w-auto py-2 pl-3 pr-8 border border-gray-200 dark:border-slate-600 rounded-lg leading-5 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out">
                        <option value="">All Statuses</option>
                        <option value="Paid" {{ request('status') === 'Paid' ? 'selected' : '' }}>Paid</option>
                        <option value="Unpaid" {{ request('status') === 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
                        <option value="Partial" {{ request('status') === 'Partial' ? 'selected' : '' }}>Partial</option>
                    </select>

                    <!-- Received By Filter -->
                    <select name="received_by" onchange="this.form.submit()"
                        class="block w-full xl:w-auto py-2 pl-3 pr-8 border border-gray-200 dark:border-slate-600 rounded-lg leading-5 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out">
                        <option value="">Any Receiver</option>
                        <option value="System" {{ request('received_by') === 'System' ? 'selected' : '' }}>System</option>
                        <option value="Administrator" {{ request('received_by') === 'Administrator' ? 'selected' : '' }}>Admin</option>
                        <option value="Secretary" {{ request('received_by') === 'Secretary' ? 'selected' : '' }}>Secretary</option>
                        <option value="Cashier" {{ request('received_by') === 'Cashier' ? 'selected' : '' }}>Cashier</option>
                    </select>

                    <!-- Clear All Filters -->
                    @if(request('search') || request('date') || request('status') || request('received_by'))
                        <a href="{{ route('transactions.index') }}" 
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg shadow-sm text-sm font-medium text-red-600 bg-white dark:bg-slate-800 hover:bg-red-50 dark:hover:bg-slate-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors whitespace-nowrap">
                            Clear All
                        </a>
                    @endif
                </div>
            </form>
        </div>

            <!-- Print Header -->
            <div class="hidden print:block text-center mb-8 pb-4 border-b border-gray-200">
                <h1 class="text-3xl font-bold text-gray-900">101 Repair Service</h1>
                <h2 class="text-xl text-gray-700 mt-1">Transactions Report</h2>
                <p class="text-sm text-gray-500 mt-1">{{ date('M d, Y h:i A') }}</p>
            </div>

            <!-- Table -->
            <div
                class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 shadow-sm overflow-hidden print:shadow-none print:border-none print:rounded-none">
                <div class="overflow-x-auto print:overflow-visible">
                    <table class="min-w-full divide-y divide-gray-200 print:w-full">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    ID
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Report ID
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Customer
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Payment Date
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Payment Due
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Received By
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider print:hidden">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200" id="transactionsTableBody">
                            @forelse($transactions as $transaction)
                                <tr class="hover:bg-gray-50 dark:bg-slate-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                        #{{ $transaction->id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 dark:text-blue-400 font-medium">
                                        @if($transaction->report_id)
                                            <a href="{{ route('services.show', $transaction->report_id) }}" class="hover:underline">
                                                #{{ $transaction->report_id }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $transaction->report->customer_name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right tabular-nums text-sm font-medium text-gray-900 dark:text-white">
                                        ₱{{ number_format($transaction->total_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusClass = match ($transaction->payment_status) {
                                                'Paid' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border dark:border-green-800/50',
                                                'Unpaid', 'Pending' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border dark:border-red-800/50',
                                                'Partial' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border dark:border-yellow-800/50',
                                                default => 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-slate-300 border dark:border-slate-600',
                                            };
                                        @endphp
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                            {{ $transaction->payment_status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                        {{ $transaction->payment_date ? \Carbon\Carbon::parse($transaction->payment_date)->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                        {{ $transaction->payment_due ? \Carbon\Carbon::parse($transaction->payment_due)->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $transaction->received_by ?? 'System' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium print:hidden">
                                        <div class="flex justify-end space-x-3">
                                            @if($transaction->payment_url && $transaction->payment_status !== 'Paid')
                                                <a href="{{ $transaction->payment_url }}" target="_blank"
                                                    class="text-green-500 hover:text-green-700 dark:text-gray-400 dark:hover:text-white transition-colors"
                                                    title="Payment Link">
                                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                                        </path>
                                                    </svg>
                                                </a>
                                            @endif
                                            <a href="{{ route('transactions.show', $transaction) }}"
                                                class="text-gray-400 hover:text-blue-600 dark:hover:text-white transition-colors" title="View">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                    </path>
                                                </svg>
                                            </a>
                                            <a href="{{ route('transactions.edit', $transaction) }}"
                                                class="text-blue-600 hover:text-blue-900 dark:text-gray-400 dark:hover:text-white transition-colors" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                    </path>
                                                </svg>
                                            </a>
                                            @if(auth()->user()->role === 'Administrator')
                                            <form action="{{ route('transactions.destroy', $transaction) }}" method="POST"
                                                class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 dark:text-gray-400 dark:hover:text-white transition-colors"
                                                    title="Archive">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-16 text-center align-middle">
                                        <div class="flex flex-col items-center justify-center">
                                            @if(request('search') || request('date') || request('status') || request('received_by'))
                                                <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                                <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">No transactions found matching your filters.</p>
                                            @else
                                                <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                </svg>
                                                <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">No transactions found.</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 dark:bg-slate-700/50 flex items-center justify-between print:hidden">
                    <div class="text-sm text-gray-500 dark:text-slate-400">
                        Showing <span class="font-medium">{{ $transactions->firstItem() ?: 0 }}</span> to
                        <span class="font-medium">{{ $transactions->lastItem() ?: 0 }}</span> of
                        <span class="font-medium">{{ $transactions->total() }}</span> entries
                    </div>
                    <div>{{ $transactions->links() }}</div>
                </div>
            </div>

    </div>

    <!-- Print Styles -->
    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .space-y-6,
            .space-y-6 * {
                visibility: visible;
            }

            .space-y-6 {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0 !important;
                padding: 0 !important;
            }

            .print\:hidden {
                display: none !important;
            }

            aside,
            header {
                display: none !important;
            }

            .bg-white dark:bg-slate-800 {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
</x-app-layout>
