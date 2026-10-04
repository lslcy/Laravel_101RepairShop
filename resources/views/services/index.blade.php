<x-app-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Service Reports</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Track and manage all service requests</p>
            </div>
            @if(auth()->user()->role === 'Administrator')
            <a href="{{ route('services.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Service Report
            </a>
            @endif
        </div>

        <!-- Filters & Search -->
        <div class="ui-card p-4 print:hidden">
            <form method="GET" action="{{ route('services.index') }}" class="flex flex-col md:flex-row gap-4" id="filterForm">
                
                <!-- Search -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="block w-full pl-10 pr-10 py-2 border border-gray-200 dark:border-slate-600 rounded-lg leading-5 bg-white dark:bg-slate-800 placeholder-gray-400 focus:outline-none focus:placeholder-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                        placeholder="Search by customer name or ID..." oninput="this.form.submit()">
                    
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
                        title="Filter by Entry Date">

                    <!-- Status Filter -->
                    <select name="status" onchange="this.form.submit()"
                        class="block w-full xl:w-auto py-2 pl-3 pr-8 border border-gray-200 dark:border-slate-600 rounded-lg leading-5 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out">
                        <option value="">All Statuses</option>
                        <option value="In Progress" {{ request('status') === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="Completed" {{ request('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
                        <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>

                    <!-- Clear All Filters -->
                    @if(request('search') || request('date') || request('status'))
                        <a href="{{ route('services.index') }}" 
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
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Customer
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Appliance
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Date In
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Technician
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200" id="servicesTableBody">
                        @forelse($services as $service)
                        <tr class="table-row-hover" data-status="{{ $service->status }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                #{{ $service->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold text-xs overflow-hidden border border-gray-200 dark:border-slate-600">
                                            @if($service->customer && $service->customer->profile_picture)
                                                <img src="{{ str_starts_with($service->customer->profile_picture, 'http') ? $service->customer->profile_picture : asset($service->customer->profile_picture) }}" alt="Profile" class="h-full w-full object-cover">
                                            @else
                                                {{ substr($service->customer_name, 0, 1) }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $service->customer_name }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                {{ $service->appliance ? $service->appliance->product . ' - ' . $service->appliance->brand : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                {{ $service->date_in ? $service->date_in->format('M d, Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                $statusClass = match($service->status) {
                                'Completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border dark:border-green-800/50',
                                'Pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border dark:border-yellow-800/50',
                                'Waiting for Parts' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 border dark:border-orange-800/50',
                                'Under Repair', 'In Progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 border dark:border-blue-800/50',
                                'Cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border dark:border-red-800/50',
                                default => 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-slate-300 border dark:border-slate-600',
                                };
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                    {{ $service->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                {{ $service->details && $service->details->technician ? $service->details->technician : 'No Assigned Technician' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-3">
                                    <a href="{{ route('services.show', $service) }}" class="text-gray-400 hover:text-blue-600 dark:hover:text-white transition-colors" title="View">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    @if(in_array(auth()->user()->role, ['Administrator', 'Technician']))
                                    <a href="{{ route('services.edit', $service) }}" class="text-blue-600 hover:text-blue-900 dark:text-gray-400 dark:hover:text-white transition-colors" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    @endif
                                    @if(auth()->user()->role === 'Administrator')
                                    <form action="{{ route('services.destroy', $service) }}" method="POST" class="inline-block m-0 p-0" id="del-service-{{ $service->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            @click="$dispatch('open-confirm', { title: 'Archive Service Report', message: 'Archive this service report? You can restore it later from the Archive.', confirmText: 'Archive', cancelText: 'Cancel', variant: 'danger', action: () => document.getElementById('del-service-{{ $service->id }}').submit() })"
                                            class="text-red-500 hover:text-red-700 dark:text-gray-400 dark:hover:text-white transition-colors bg-transparent border-0 p-0 flex items-center" title="Archive">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center align-middle">
                                <div class="flex flex-col items-center justify-center">
                                    @if(request('search') || request('status') || request('date'))
                                        <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">No service reports found matching your filters.</p>
                                    @else
                                        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">No service reports found.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 dark:bg-slate-700/50 flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-slate-400">
                    Showing <span class="font-medium">{{ $services->firstItem() ?: 0 }}</span> to
                    <span class="font-medium">{{ $services->lastItem() ?: 0 }}</span> of
                    <span class="font-medium">{{ $services->total() }}</span> entries
                </div>
                <div>{{ $services->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
