<x-app-layout>
    <div class="ui-page">
        <!-- Header -->
        <div class="ui-page-header">
            <div>
                <h2 class="ui-page-title">Parts Inventory</h2>
                <p class="ui-page-subtitle">Manage spare parts and inventory</p>
            </div>
            <a href="{{ route('inventory.create') }}"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Part
            </a>
        </div>

        <!-- Filters & Search -->
        <div class="ui-card p-4 print:hidden">
            <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-col md:flex-row gap-4" id="filterForm">

                <!-- Search -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="block w-full pl-10 pr-10 py-2 border border-gray-200 dark:border-slate-600 rounded-lg leading-5 bg-white dark:bg-slate-800 placeholder-gray-400 focus:outline-none focus:placeholder-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                        placeholder="Search parts by name or part number..." oninput="this.form.submit()">

                    @if(request('search'))
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button type="button" onclick="document.querySelector('input[name=search]').value=''; document.getElementById('filterForm').submit();" class="text-gray-400 hover:text-red-500 focus:outline-none transition-colors" title="Clear Search">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    @endif
                </div>

                <!-- Filters -->
                <div class="flex flex-col xl:flex-row gap-4">
                    <!-- Status Filter -->
                    <select name="status" onchange="this.form.submit()"
                        class="block w-full xl:w-auto py-2 pl-3 pr-8 border border-gray-200 dark:border-slate-600 rounded-lg leading-5 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out">
                        <option value="">All Stock Statuses</option>
                        <option value="In Stock" {{ request('status') === 'In Stock' ? 'selected' : '' }}>In Stock</option>
                        <option value="Low Stock" {{ request('status') === 'Low Stock' ? 'selected' : '' }}>Low Stock</option>
                        <option value="Critical" {{ request('status') === 'Critical' ? 'selected' : '' }}>Critical</option>
                        <option value="Out of Stock" {{ request('status') === 'Out of Stock' ? 'selected' : '' }}>Out of Stock</option>
                    </select>

                    <!-- Clear All Filters -->
                    @if(request('search') || request('status'))
                    <a href="{{ route('inventory.index') }}"
                        class="inline-flex items-center justify-center px-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg shadow-sm text-sm font-medium text-red-600 bg-white dark:bg-slate-800 hover:bg-red-50 dark:hover:bg-slate-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors whitespace-nowrap">
                        Clear All
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="ui-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Part No
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Part Name
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Price
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Stock
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200" id="inventoryTableBody">
                        @forelse($parts as $part)
                        @php
                            $qty = $part->quantity_stock;
                            $maxDisplay = 50;
                            $barPercent = min(100, ($qty / $maxDisplay) * 100);
                            if ($qty === 0) {
                                $statusLabel = 'Out of Stock';
                                $statusTone = 'danger';
                                $barColor = 'bg-red-500';
                            } elseif ($qty < 5) {
                                $statusLabel = 'Critical';
                                $statusTone = 'danger';
                                $barColor = 'bg-red-500';
                            } elseif ($qty < 10) {
                                $statusLabel = 'Low Stock';
                                $statusTone = 'warning';
                                $barColor = 'bg-yellow-500';
                            } else {
                                $statusLabel = 'In Stock';
                                $statusTone = 'success';
                                $barColor = 'bg-green-500';
                            }
                        @endphp
                        <tr class="table-row-hover">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-purple-600 rounded-lg flex items-center justify-center text-white">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                                            </path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $part->part_no }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                {{ $part->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right tabular-nums text-sm font-bold text-green-600">
                                &#8369;{{ number_format($part->price, 2) }}
                            </td>

                            <!-- Enhanced Stock Indicator -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex flex-col gap-1.5 min-w-[155px] mx-auto w-max">
                                    <!-- Quantity and stock status -->
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $qty }}
                                            <span class="font-normal text-xs text-gray-400 dark:text-slate-500">units</span>
                                        </span>
                                        <x-status-badge :status="$statusLabel" :tone="$statusTone" />
                                    </div>
                                    <!-- Progress bar -->
                                    <div class="w-full bg-gray-200 dark:bg-slate-600 rounded-full h-1.5 overflow-hidden">
                                        <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ $barPercent }}%"></div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-3">
                                    <a href="{{ route('inventory.edit', $part) }}"
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-900 transition-colors" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                    </a>
                                    <form action="{{ route('inventory.destroy', $part) }}" method="POST"
                                        class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 transition-colors"
                                            title="Archive">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4">
                                                </path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center align-middle">
                                    <div class="flex flex-col items-center justify-center">
                                        @if(request('search') || request('status'))
                                        <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">No parts found matching your filters.</p>
                                        @else
                                        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">No parts found.</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="ui-pagination">
                <div class="text-sm text-gray-500 dark:text-slate-400">
                    Showing <span class="font-medium">{{ $parts->firstItem() ?: 0 }}</span> to
                    <span class="font-medium">{{ $parts->lastItem() ?: 0 }}</span> of
                    <span class="font-medium">{{ $parts->total() }}</span> entries
                </div>
                <div>{{ $parts->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
