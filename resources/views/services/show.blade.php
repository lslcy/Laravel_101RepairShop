<x-app-layout>
    <div class="ui-page">
        <!-- Header -->
        <div class="ui-page-header">
            <div>
                <h2 class="ui-page-title">Service Report #{{ $service->id }}</h2>
                <div class="ui-page-subtitle flex flex-wrap items-center gap-4">
                    <span>Date In: {{ $service->date_in ? $service->date_in->format('M d, Y') : 'N/A' }}</span>
                    <span>&bull;</span>
                    <x-status-badge :status="$service->status" />
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('services.print', $service) }}" target="_blank"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-slate-500 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:bg-slate-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-5 h-5 mr-2 -ml-1 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                        </path>
                    </svg>
                    Print
                </a>
                @if(in_array(auth()->user()->role, ['Administrator', 'Technician']))
                    <a href="{{ route('services.edit', $service) }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        Edit Report
                    </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Details Card -->
                <div class="ui-card overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Appliance Details</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Appliance</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $service->appliance ? $service->appliance->product . ' (' . $service->appliance->brand . ')' : 'N/A' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Model No. / Dealer</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $service->appliance && $service->appliance->model_no ? $service->appliance->model_no : 'N/A' }}
                                {{ $service->dealer ? ' / ' . $service->dealer : '' }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Problem Description</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-slate-700/50 p-3 rounded-lg border border-gray-200 dark:border-slate-700">
                                {{ $service->problem_desc }}
                            </dd>
                        </div>
                        @if($service->findings)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Findings</dt>
                                <dd
                                    class="mt-1 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-slate-700/50 p-3 rounded-lg border border-gray-200 dark:border-slate-700 whitespace-pre-line">
                                    {{ $service->findings }}
                                </dd>
                            </div>
                        @endif
                        @if($service->remarks)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Remarks</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $service->remarks }}</dd>
                            </div>
                        @endif
                        @if($service->used_parts)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Miscellaneous Notes (Not in Inventory)</dt>
                                <dd
                                    class="mt-1 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-slate-700/50 p-3 rounded-lg border border-gray-200 dark:border-slate-700 whitespace-pre-line">
                                    {{ $service->used_parts }}
                                </dd>
                            </div>
                        @endif
                        @if($service->parts && $service->parts->count() > 0)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Inventory Parts Used</dt>
                                <dd class="mt-1 border border-gray-200 dark:border-slate-600 rounded-md overflow-hidden bg-white dark:bg-slate-800">
                                    <table class="ui-table">
                                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-slate-400">Part No. / Name</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-slate-400">Qty</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-slate-400">Not Working</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-slate-400">Price</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-slate-400">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($service->parts as $part)
                                                <tr>
                                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white flex items-center space-x-2">
                                                        <span>{{ $part->part_no }} - {{ $part->description ?: $part->name }}</span>
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-center text-gray-900 dark:text-white">{{ $part->pivot->quantity }}</td>
                                                    <td class="px-4 py-2 text-sm text-center">
                                                        @if($part->pivot->is_not_working)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Yes</span>
                                                        @else
                                                            <span class="text-gray-400 dark:text-slate-500">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                                        @if($part->pivot->is_not_working)
                                                            <span class="text-gray-400 dark:text-slate-500">-</span>
                                                        @else
                                                            ₱{{ number_format($part->pivot->price, 2) }}
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                                        @if($part->pivot->is_not_working)
                                                            <span class="text-gray-400 dark:text-slate-500">-</span>
                                                        @else
                                                            ₱{{ number_format($part->pivot->quantity * $part->pivot->price, 2) }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </dd>
                            </div>
                        @endif
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Assigned Technician(s)</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                @php
                                    $assignedTechs = [];
                                    if ($service->details && !empty($service->details->technician)) {
                                        $assignedTechs = array_values(array_filter(array_map('trim', explode(',', $service->details->technician))));
                                    }
                                @endphp

                                @if(empty($assignedTechs))
                                    No Assigned Technician
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($assignedTechs as $techName)
                                            @php
                                                $status = $techStatusMap[strtolower($techName)] ?? 'Unknown';
                                            @endphp

                                            <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-gray-50 dark:bg-slate-700/50 border border-gray-200 dark:border-slate-600">
                                                <span class="font-medium">{{ $techName }}</span>
                                                <x-status-badge :status="$status" />
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Service Types</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                @if($service->details && $service->details->service_types)
                                    @foreach($service->details->service_types as $type)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                                            {{ $type }}
                                        </span>
                                    @endforeach
                                @else
                                    N/A
                                @endif
                            </dd>
                        </div>

                        <!-- Attachments Display -->
                        @if (!empty($service->attachments))
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-slate-400 mb-2">Attachments</dt>
                            <dd class="mt-1 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg p-3 flex flex-wrap gap-3">
                                @foreach($service->attachments as $attachment)
                                    <a href="{{ $attachment['url'] }}" target="_blank" class="flex items-center space-x-2 text-sm text-blue-700 hover:text-blue-900 bg-blue-50 border border-blue-100 px-3 py-2 rounded-md hover:shadow-sm transition-all group">
                                        @if(isset($attachment['resource_type']) && $attachment['resource_type'] === 'image')
                                            <svg class="w-5 h-5 text-blue-400 group-hover:text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L28 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        @else
                                            <svg class="w-5 h-5 text-blue-400 group-hover:text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                        @endif
                                        <span class="truncate max-w-[200px] font-medium">{{ $attachment['original_name'] ?? 'View File' }}</span>
                                    </a>
                                @endforeach
                            </dd>
                        </div>
                        @endif

                        <div class="sm:col-span-2 bg-blue-50 dark:bg-slate-700/50 p-4 rounded-lg border dark:border-slate-600">
                            <dt class="text-sm font-bold text-gray-700 dark:text-slate-200 mb-2">Cost Breakdown</dt>
                            <dd class="mt-1 grid grid-cols-1 sm:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-slate-400 block">Labor Cost</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">₱{{ number_format($service->details ? $service->details->labor : 0, 2) }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-slate-400 block">Parts Total</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">₱{{ number_format($service->details ? $service->details->parts_total_charge : 0, 2) }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-slate-400 block">Misc. Cost</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">₱{{ number_format($service->details ? $service->details->miscellaneous_cost : 0, 2) }}</span>
                                </div>
                                <div class="sm:border-l sm:border-blue-200 sm:pl-4">
                                    <span class="text-gray-500 dark:text-slate-400 block">Total Estimated</span>
                                    <span class="font-bold text-blue-900 text-lg">₱{{ number_format($service->details ? $service->details->total_amount : 0, 2) }}</span>
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="ui-card overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Progress Log & Comments</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4 mb-6 max-h-96 overflow-y-auto pr-2">
                            @if(count($service->comments) > 0)
                                @foreach($service->comments as $comment)
                                <div class="flex space-x-3">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 dark:text-blue-400 text-xs font-bold overflow-hidden border border-gray-200 dark:border-slate-600">
                                            @if($comment->user && $comment->user->profile_picture)
                                                <img src="{{ $comment->user->profile_picture }}"
                                                    alt="{{ $comment->user->name }}" class="h-full w-full object-cover">
                                            @else
                                                {{ substr($comment->user->name ?? 'U', 0, 1) }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-1 bg-gray-50 dark:bg-slate-700/50 p-3 rounded-lg rounded-tl-none">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ $comment->user->name ?? 'Unknown' }}
                                            </h4>
                                            <span
                                                class="text-xs text-gray-500 dark:text-slate-400">{{ $comment->created_at->format('M d, Y h:i A') }}</span>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-700 dark:text-slate-200">{{ $comment->comment_text }}</p>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="text-center py-6 text-gray-500 dark:text-slate-400 text-sm">
                                    No comments yet. Start the conversation!
                                </div>
                            @endif
                        </div>

                        <form action="{{ route('services.comments.store', $service) }}" method="POST" class="relative">
                            @csrf
                            <input type="text" name="comment_text" required
                                class="block w-full pl-4 pr-12 py-3 border-gray-300 dark:border-slate-500 rounded-lg focus:ring-blue-500 focus:border-blue-500 sm:text-sm shadow-sm"
                                placeholder="Add a status update or comment...">
                            <button type="submit"
                                class="absolute right-2 top-2 p-1 text-blue-600 dark:text-blue-400 hover:text-blue-800 rounded transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Customer Card -->
                <div class="ui-card overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Customer Info</h3>
                    </div>
                    <div class="p-6">
                        @if($service->customer)
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="flex-shrink-0">
                                    <div
                                        class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold">
                                        {{ substr($service->customer->first_name, 0, 1) }}
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $service->customer->first_name }}
                                        {{ $service->customer->last_name }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">Customer ID: #{{ $service->customer->id }}</p>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center text-gray-600 dark:text-slate-300">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                        </path>
                                    </svg>
                                    {{ $service->customer->phone_no }}
                                </div>
                                <div class="flex items-start text-gray-600 dark:text-slate-300">
                                    <svg class="w-4 h-4 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    {{ $service->customer->address ?? 'No address provided' }}
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-700 text-center">
                                <a href="{{ route('customers.show', $service->customer) }}"
                                    class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500">View Profile</a>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $service->customer_name }}</p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">Guest / Unlinked</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Payments Card -->
                <div class="ui-card overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Payments</h3>
                        @if(in_array(auth()->user()->role, ['Administrator', 'Cashier']))
                            @if($service->status === 'Completed')
                                @if($service->transactions->where('payment_status', 'Paid')->isEmpty())
                                    <a href="{{ route('transactions.create', ['report_id' => $service->id]) }}"
                                        class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 hover:underline">
                                        Record Payment
                                    </a>
                                @endif
                            @else
                                <span
                                    class="text-xs font-medium text-gray-400 cursor-not-allowed border-b border-dashed border-gray-400 pb-0.5"
                                    title="Report must be Completed first">
                                    Payment Restricted
                                </span>
                            @endif
                        @endif
                    </div>
                    <div class="p-0">
                        @if($service->transactions->count() > 0)
                            <table class="ui-table">
                                <thead class="bg-gray-50 dark:bg-slate-700/50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-slate-400">Total</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-slate-400">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-100">
                                    @foreach($service->transactions as $transaction)
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                ₱{{ number_format($transaction->total_amount, 2) }}</td>
                                            <td class="px-4 py-2 text-right">
                                                <x-status-badge :status="$transaction->payment_status" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="p-6 text-center text-sm text-gray-500 dark:text-slate-400">
                                No associated payments.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
