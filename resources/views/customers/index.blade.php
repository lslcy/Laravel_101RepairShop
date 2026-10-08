<x-app-layout>
    <div class="ui-page">
        <!-- Header -->
        <div class="ui-page-header">
            <div>
                <h2 class="ui-page-title">Customer Management</h2>
                <p class="ui-page-subtitle">Manage your customer information and records
                </p>
            </div>
            @if(auth()->user()->role === 'Administrator')
                <a href="{{ route('customers.create') }}"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Customer
                </a>
            @endif
        </div>

        <!-- Filters & Search -->
        <div
            class="ui-card p-4 print:hidden">
            <form method="GET" action="{{ route('customers.index') }}" class="flex flex-col md:flex-row gap-4"
                id="filterForm">

                <!-- Search -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="block w-full pl-10 pr-10 py-2 border border-gray-200 dark:border-slate-600 rounded-lg leading-5 bg-white dark:bg-slate-800 placeholder-gray-400 focus:outline-none focus:placeholder-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                        placeholder="Search customers by name or phone..." oninput="this.form.submit()">

                    @if(request('search'))
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <button type="button"
                                onclick="document.querySelector('input[name=search]').value=''; document.getElementById('filterForm').submit();"
                                class="text-gray-400 hover:text-red-500 focus:outline-none transition-colors"
                                title="Clear Search">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Filters -->
                <div class="flex flex-col xl:flex-row gap-4">
                    <!-- Appliances Filter -->
                    <select name="has_appliances" onchange="this.form.submit()"
                        class="block w-full xl:w-auto py-2 pl-3 pr-8 border border-gray-200 dark:border-slate-600 rounded-lg leading-5 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out">
                        <option value="">Any Appliances</option>
                        <option value="yes" {{ request('has_appliances') === 'yes' ? 'selected' : '' }}>Has Appliances
                        </option>
                        <option value="no" {{ request('has_appliances') === 'no' ? 'selected' : '' }}>No Appliances
                        </option>
                    </select>

                    <!-- Clear All Filters -->
                    @if(request('search') || request('has_appliances'))
                        <a href="{{ route('customers.index') }}"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg shadow-sm text-sm font-medium text-red-600 bg-white dark:bg-slate-800 hover:bg-red-50 dark:hover:bg-slate-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors whitespace-nowrap">
                            Clear All
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table -->
        <div
            class="ui-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                ID
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Customer
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Contact
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Address
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Appliances
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200" id="customersTableBody">
                        @forelse($customers as $customer)
                            <tr class="table-row-hover">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                    #{{ $customers->firstItem() + $loop->index }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div
                                                class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-sm overflow-hidden border border-gray-200 dark:border-slate-600">
                                                @if($customer->profile_picture)
                                                    <img src="{{ str_starts_with($customer->profile_picture, 'http') ? $customer->profile_picture : asset($customer->profile_picture) }}"
                                                        alt="Profile" class="h-full w-full object-cover">
                                                @else
                                                    {{ substr($customer->first_name, 0, 1) }}{{ substr($customer->last_name, 0, 1) }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $customer->first_name }} {{ $customer->last_name }}
                                            </div>
                                            <!-- Email placeholder if needed, otherwise omitted -->
                                            @if($customer->auth_id)
                                                <span class="mt-0.5 inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400" title="Linked to a mobile app account">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                                    Mobile
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-500 dark:text-slate-400">
                                        <svg class="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                            </path>
                                        </svg>
                                        {{ $customer->phone_no }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                    {{ Str::limit($customer->address, 30) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $customer->appliances->count() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-3">
                                        <!-- View -->
                                        <a href="{{ route('customers.show', $customer) }}"
                                            class="text-gray-400 hover:text-blue-600 dark:text-blue-400 p-1 hover:bg-blue-50 rounded transition-colors"
                                            title="View">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                        </a>
                                        @if(auth()->user()->role === 'Administrator')
                                            <a href="{{ route('customers.edit', $customer) }}"
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 p-1 hover:bg-blue-50 rounded transition-colors"
                                                title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                    </path>
                                                </svg>
                                            </a>
                                            <form action="{{ route('customers.destroy', $customer) }}" method="POST"
                                                class="inline-block" id="del-cust-{{ $customer->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                    @click="$dispatch('open-confirm', { title: 'Archive Customer', message: 'Archive customer {{ addslashes($customer->first_name . ' ' . $customer->last_name) }}? You can restore this later from the Archive.', confirmText: 'Archive', cancelText: 'Cancel', variant: 'danger', action: () => document.getElementById('del-cust-{{ $customer->id }}').submit() })"
                                                    class="text-red-500 hover:text-red-700 p-1 hover:bg-red-50 rounded transition-colors"
                                                    title="Archive">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400">Restricted</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center align-middle">
                                    <div class="flex flex-col items-center justify-center">
                                        @if(request('search') || request('has_appliances'))
                                            <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-3" fill="none"
                                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">No customers found
                                                matching your filters.</p>
                                        @else
                                            <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                                </path>
                                            </svg>
                                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">No customers
                                                found.</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                class="ui-pagination">
                <div class="text-sm text-gray-500 dark:text-slate-400">
                    Showing <span class="font-medium">{{ $customers->firstItem() ?: 0 }}</span> to
                    <span class="font-medium">{{ $customers->lastItem() ?: 0 }}</span> of
                    <span class="font-medium">{{ $customers->total() }}</span> entries
                </div>
                <div>{{ $customers->links() }}</div>
            </div>
        </div>
    </div>

</x-app-layout>
