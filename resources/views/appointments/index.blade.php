<x-app-layout>
    <div class="ui-page">
        <!-- Header -->
        <div class="ui-page-header">
            <div>
                <h2 class="ui-page-title">Appointments</h2>
            </div>
        </div>

        <!-- Filters -->
        <div class="ui-card p-4">
            <form method="GET" action="{{ route('appointments.index') }}" class="flex flex-col md:flex-row gap-4" id="appointmentFilterForm">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <label for="appointment-search" class="sr-only">Search appointments</label>
                    <input type="text" name="search" id="appointment-search" value="{{ $search }}"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Search by customer, appliance, title or notes...">
                </div>
                <label for="appointment-status-filter" class="sr-only">Appointment status</label>
                <select name="status" id="appointment-status-filter" onchange="this.form.submit()"
                    class="block w-full md:w-auto py-2 pl-3 pr-8 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\Appointment::STATUSES as $s)
                        <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
                <label for="appointment-date-filter" class="sr-only">Appointment date</label>
                <input type="date" name="date" id="appointment-date-filter" value="{{ $date }}" onchange="this.form.submit()"
                    class="block w-full md:w-auto py-2 px-3 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                @if($search || $status || $date)
                    <a href="{{ route('appointments.index') }}" id="appointment-clear-filters"
                        class="inline-flex items-center justify-center px-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg shadow-sm text-sm font-medium text-red-600 bg-white dark:bg-slate-800 hover:bg-red-50 dark:hover:bg-slate-700/50 transition-colors whitespace-nowrap">
                        Clear All
                    </a>
                @endif
            </form>
        </div>

        <!-- Table -->
        <div class="ui-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left">Schedule</th>
                            <th scope="col" class="px-6 py-3 text-left">Customer</th>
                            <th scope="col" class="px-6 py-3 text-left">Request</th>
                            <th scope="col" class="px-6 py-3 text-left">Status</th>
                            <th scope="col" class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                        @forelse($appointments as $appointment)
                            @php
                                $isOpen = in_array($appointment->status, ['Pending', 'Confirmed']);
                                $isOverdue = $isOpen && $appointment->appointment_date && $appointment->appointment_date->isPast() && !$appointment->appointment_date->isToday();
                            @endphp
                            <tr class="table-row-hover" id="appointment-row-{{ $appointment->id }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $appointment->appointment_date?->timezone(config('app.timezone'))->format('M d, Y') ?? '—' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ $appointment->time_slot ?: 'No time slot' }}</div>
                                    @if($isOverdue)
                                        <span class="mt-1 inline-flex text-[10px] font-semibold uppercase text-red-600 dark:text-red-400">Overdue</span>
                                    @elseif($isOpen && $appointment->appointment_date?->isToday())
                                        <span class="mt-1 inline-flex text-[10px] font-semibold uppercase text-blue-600 dark:text-blue-400">Today</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($appointment->customer)
                                        <a href="{{ route('customers.show', $appointment->customer) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                                            {{ trim($appointment->customer->first_name . ' ' . $appointment->customer->last_name) ?: 'Unnamed customer' }}
                                        </a>
                                        <div class="text-xs text-gray-500 dark:text-slate-400">{{ $appointment->customer->phone_no ?: $appointment->customer->email }}</div>
                                        @if($appointment->customer->trashed())
                                            <span class="text-[10px] font-semibold uppercase text-red-500">Archived</span>
                                        @endif
                                    @else
                                        <span class="text-sm text-gray-400 italic">Unknown customer</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $appointment->title }}</div>
                                    @if($appointment->appliance_name)
                                        <div class="text-xs text-gray-500 dark:text-slate-400">{{ $appointment->appliance_name }}</div>
                                    @endif
                                    @if($appointment->notes)
                                        <div class="mt-1 text-xs text-gray-500 dark:text-slate-400 max-w-xs truncate" title="{{ $appointment->notes }}">{{ $appointment->notes }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-status-badge :status="$appointment->status ?? 'Pending'" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="flex justify-end items-center gap-2">
                                        @if($appointment->status === 'Pending')
                                            <form method="POST" action="{{ route('appointments.update', $appointment) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="Confirmed">
                                                <button type="submit" id="appointment-confirm-{{ $appointment->id }}"
                                                    class="px-2.5 py-1 rounded-md text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 transition-colors">
                                                    Confirm
                                                </button>
                                            </form>
                                        @endif

                                        @if($isOpen)
                                            <form method="POST" action="{{ route('appointments.convert', $appointment) }}">
                                                @csrf
                                                <button type="submit" id="appointment-convert-{{ $appointment->id }}" title="Open a new service report pre-filled from this appointment"
                                                    class="px-2.5 py-1 rounded-md text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                                    Create Report
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('appointments.update', $appointment) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="Completed">
                                                <button type="submit" id="appointment-complete-{{ $appointment->id }}"
                                                    class="px-2.5 py-1 rounded-md text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-300 dark:hover:bg-green-900/50 transition-colors">
                                                    Complete
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('appointments.update', $appointment) }}" id="appointment-cancel-form-{{ $appointment->id }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="Cancelled">
                                                <button type="button" id="appointment-cancel-{{ $appointment->id }}"
                                                    @click="$dispatch('open-confirm', { title: 'Cancel Appointment', message: 'Cancel appointment #{{ $appointment->id }}? The customer will see it as Cancelled in the app.', confirmText: 'Cancel Appointment', cancelText: 'Keep', variant: 'danger', action: () => document.getElementById('appointment-cancel-form-{{ $appointment->id }}').submit() })"
                                                    class="px-2.5 py-1 rounded-md text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50 transition-colors">
                                                    Cancel
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('appointments.update', $appointment) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="Pending">
                                                <button type="submit" id="appointment-reopen-{{ $appointment->id }}"
                                                    class="px-2.5 py-1 rounded-md text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 transition-colors">
                                                    Reopen
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                                        {{ ($search || $status || $date) ? 'No appointments match your filters.' : 'No appointments have been booked yet.' }}
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ui-pagination">
                <div class="text-sm text-gray-500 dark:text-slate-400">
                    Showing <span class="font-medium">{{ $appointments->firstItem() ?: 0 }}</span> to
                    <span class="font-medium">{{ $appointments->lastItem() ?: 0 }}</span> of
                    <span class="font-medium">{{ $appointments->total() }}</span> entries
                </div>
                <div>{{ $appointments->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
