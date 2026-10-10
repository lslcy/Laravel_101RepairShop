<x-app-layout>
    <div class="ui-page">
        <!-- Header -->
        <div class="ui-page-header">
            <div>
                <h2 class="ui-page-title">{{ $customer->first_name }} {{ $customer->last_name }}</h2>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('customers.index') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-slate-500 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white dark:bg-slate-800 table-row-hover">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to List
                </a>
                @if(auth()->user()->role === 'Administrator')
                    <a href="{{ route('customers.edit', $customer) }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Customer Info Card -->
            <div class="ui-card overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Contact Information</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="h-14 w-14 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-xl">
                            {{ substr($customer->first_name, 0, 1) }}{{ substr($customer->last_name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $customer->first_name }} {{ $customer->last_name }}</p>
                            <p class="text-xs text-gray-400">Customer #{{ $customer->id }}</p>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2 text-gray-600 dark:text-slate-300">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            {{ $customer->phone_no }}
                        </div>
                        @if($customer->email)
                        <div class="flex items-center gap-2 text-gray-600 dark:text-slate-300">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            {{ $customer->email }}
                        </div>
                        @endif
                        @if($customer->address)
                        <div class="flex items-start gap-2 text-gray-600 dark:text-slate-300">
                            <svg class="w-4 h-4 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ $customer->address }}
                        </div>
                        @endif
                    </div>
                    <div class="pt-3 border-t border-gray-100 dark:border-slate-700 grid grid-cols-2 gap-3 text-center">
                        <div class="bg-blue-50 rounded-lg p-3">
                            <p class="text-2xl font-bold text-blue-700">{{ $customer->appliances->count() }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Appliances</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-3">
                            <p class="text-2xl font-bold text-green-700">{{ $customer->serviceReports->count() }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Service Reports</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appliances & Service History -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Appliances -->
                <div class="ui-card overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Appliances ({{ $customer->appliances->count() }})</h3>
                    </div>
                    @if($customer->appliances->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="ui-table">
                                <thead class="bg-gray-50 dark:bg-slate-700/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">Type / Brand</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">Model / Serial</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">Size</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">Warranty</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200">
                                    @foreach($customer->appliances as $app)
                                        <tr>
                                            <td class="px-4 py-3 text-sm">
                                                <p class="font-medium text-gray-900 dark:text-white">{{ $app->product }}</p>
                                                <p class="text-gray-500 dark:text-slate-400">{{ $app->brand }}</p>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-slate-400">
                                                <p>{{ $app->model_no ?? '-' }}</p>
                                                <p class="text-xs">{{ $app->serial_no ?? '' }}</p>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-slate-400">{{ $app->appliance_size ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                @if($app->warranty_end)
                                                    @if(\Carbon\Carbon::parse($app->warranty_end)->isPast())
                                                        <x-status-badge status="Expired" tone="danger" />
                                                    @else
                                                        <x-status-badge :status="'Active until ' . \Carbon\Carbon::parse($app->warranty_end)->format('M d, Y')" tone="success" />
                                                    @endif
                                                @else
                                                    <x-status-badge status="No Warranty" tone="neutral" />
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-6 text-center text-sm text-gray-500 dark:text-slate-400">No appliances registered yet.</div>
                    @endif
                </div>

                <!-- Service History -->
                <div class="ui-card overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Service History ({{ $customer->serviceReports->count() }})</h3>
                    </div>
                    @if($customer->serviceReports->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="ui-table">
                                <thead class="bg-gray-50 dark:bg-slate-700/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">Report</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">Appliance</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200">
                                    @foreach($customer->serviceReports as $report)
                                        <tr class="table-row-hover">
                                            <td class="px-4 py-3 text-sm font-medium text-blue-600 dark:text-blue-400">#{{ $report->id }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-slate-200">{{ $report->appliance ? $report->appliance->product . ' - ' . $report->appliance->brand : 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-slate-400">{{ $report->date_in ? $report->date_in->format('M d, Y') : '-' }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <x-status-badge :status="$report->status" />
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ route('services.show', $report) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 text-xs font-medium">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-6 text-center text-sm text-gray-500 dark:text-slate-400">No service records yet.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
