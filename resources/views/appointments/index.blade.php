<x-app-layout>
    @php
        $statusStyles = [
            'Pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border dark:border-amber-800/50',
            'Confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 border dark:border-blue-800/50',
            'Completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border dark:border-green-800/50',
            'Cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border dark:border-red-800/50',
        ];
        $cardAccents = [
            'Pending' => 'text-amber-600 dark:text-amber-400',
            'Confirmed' => 'text-blue-600 dark:text-blue-400',
            'Completed' => 'text-green-600 dark:text-green-400',
            'Cancelled' => 'text-red-600 dark:text-red-400',
        ];
    @endphp

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Appointments</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Bookings made by customers from the mobile app</p>
            </div>
        </div>

        <!-- Status summary -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach(\App\Models\Appointment::STATUSES as $s)
                <a href="{{ route('appointments.index', array_merge(request()->except('page'), ['status' => $status === $s ? null : $s])) }}"
                    id="appointment-status-card-{{ strtolower($s) }}"
                    class="bg-white dark:bg-slate-800 p-4 rounded-lg border shadow-sm transition-all hover:shadow-md hover:-translate-y-0.5 {{ $status === $s ? 'border-blue-500 ring-1 ring-blue-500' : 'border-gray-200 dark:border-slate-700' }}">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ $s }}</p>
                    <p class="mt-1 text-2xl font-bold {{ $cardAccents[$s] }}">{{ $counts[$s] ?? 0 }}</p>
                </a>
            @endforeach
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-slate-800 p-4 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm">
            <form method="GET" action="{{ route('appointments.index') }}" class="flex flex-col md:flex-row gap-4" id="appointmentFilterForm">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" name="search" id="appointment-search" value="{{ $search }}"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Search by customer, appliance, title or notes...">
                </div>
                <select name="status" id="appointment-status-filter" onchange="this.form.submit()"
                    class="block w-full md:w-auto py-2 pl-3 pr-8 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\Appointment::STATUSES as $s)
                        <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
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
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Schedule</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Request</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                        @forelse($appointments as $appointment)
                            @php
                                $isOpen = in_array($appointment->status, ['Pending', 'Confirmed']);
                                $isOverdue = $isOpen && $appointment->appointment_date && $appointment->appointment_date->isPast() && !$appointment->appointment_date->isToday();
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors" id="appointment-row-{{ $appointment->id }}">
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
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusStyles[$appointment->status] ?? 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-slate-300' }}">
                                        {{ $appointment->status ?? 'Pending' }}
                                    </span>
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

            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50 flex items-center justify-between">
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
