<x-app-layout>
    <div class="ui-page">
        <!-- Header -->
        <div class="ui-page-header">
            <h2 class="ui-page-title">Edit Transaction #{{ $transaction->id }}</h2>
            <a href="{{ route('transactions.index') }}"
                class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-white flex items-center transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to List
            </a>
        </div>

        <!-- Form Card -->
        <div class="ui-card overflow-hidden">
            <div class="p-6">
                <form action="{{ route('transactions.update', $transaction) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <!-- Service Report (Read Only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200">Service Report</label>
                            <input type="text" disabled
                                value="{{ $transaction->report_id ? '#' . $transaction->report_id . ' - ' . ($transaction->report->customer ? $transaction->report->customer->first_name : $transaction->report->customer_name) : 'N/A' }}"
                                class="mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 dark:bg-slate-700/50 text-gray-500 dark:text-slate-400 shadow-sm sm:text-sm">
                            <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">Service report linkage cannot be changed.</p>
                        </div>

                        <!-- Total Amount -->
                        <div>
                            <label for="total_amount" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Total
                                Amount</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-slate-400 sm:text-sm">₱</span>
                                </div>
                                <input type="number" name="total_amount" id="total_amount" step="0.01"
                                    value="{{ old('total_amount', $transaction->total_amount) }}" required
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-7 sm:text-sm border-gray-300 dark:border-slate-500 rounded-lg">
                            </div>
                            @error('total_amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Payment Details Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="{ payment_status: '{{ old('payment_status', $transaction->payment_status) }}' }">
                            <!-- Payment Status -->
                            <div>
                                <label for="payment_status" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Payment
                                    Status</label>
                                <select id="payment_status" name="payment_status" x-model="payment_status"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-slate-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg">
                                    <option value="Unpaid">Unpaid</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Partial">Partial</option>
                                </select>
                                @error('payment_status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Payment Date -->
                            <div>
                                <label for="payment_date" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Payment Date</label>
                                <input type="date" name="payment_date" id="payment_date"
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-slate-500 rounded-lg"
                                    value="{{ old('payment_date', optional($transaction->payment_date)->format('Y-m-d')) }}">
                                @error('payment_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Payment Due -->
                            <div>
                                <label for="payment_due" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Payment Due</label>
                                <input type="date" name="payment_due" id="payment_due"
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-slate-500 rounded-lg"
                                    value="{{ old('payment_due', optional($transaction->payment_due)->format('Y-m-d')) }}">
                                @error('payment_due')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Partial Amount -->
                            <div x-show="payment_status === 'Partial'" x-cloak>
                                <label for="partial_payment_amount" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Partial Amount Paid</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 dark:text-slate-400 sm:text-sm">₱</span>
                                    </div>
                                    <input type="number" name="partial_payment_amount" id="partial_payment_amount" step="0.01"
                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-7 sm:text-sm border-gray-300 dark:border-slate-500 rounded-lg"
                                        placeholder="0.00" value="{{ old('partial_payment_amount', $transaction->partial_payment_amount) }}">
                                </div>
                                @error('partial_payment_amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Payment Method -->
                            <div>
                                <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Payment Method</label>
                                <select id="payment_method" name="payment_method"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-slate-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg">
                                    <option value="">Select Method</option>
                                    <option value="Cash" {{ old('payment_method', $transaction->payment_method) == 'Cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="GCash" {{ old('payment_method', $transaction->payment_method) == 'GCash' ? 'selected' : '' }}>GCash</option>
                                    <option value="Card" {{ old('payment_method', $transaction->payment_method) == 'Card' ? 'selected' : '' }}>Card</option>
                                    <option value="PayMaya" {{ old('payment_method', $transaction->payment_method) == 'PayMaya' ? 'selected' : '' }}>PayMaya</option>
                                    <option value="Bank Transfer" {{ old('payment_method', $transaction->payment_method) == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="Check" {{ old('payment_method', $transaction->payment_method) == 'Check' ? 'selected' : '' }}>Check</option>
                                </select>
                                @error('payment_method')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Reference No -->
                            <div>
                                <label for="reference_no" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Reference Number <span class="text-gray-400 text-xs">(If applicable)</span></label>
                                <input type="text" name="reference_no" id="reference_no"
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-slate-500 rounded-lg"
                                    placeholder="e.g. 10023940192" value="{{ old('reference_no', $transaction->reference_no) }}">
                                @error('reference_no')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Received By -->
                            <div>
                                <label for="received_by" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Received By <span class="text-gray-400 text-xs">(Defaults to {{ auth()->user()->first_name }} {{ auth()->user()->last_name }})</span></label>
                                <input type="text" name="received_by" id="received_by"
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-slate-500 rounded-lg"
                                    placeholder="Name of Cashier/Admin" value="{{ old('received_by', $transaction->received_by && $transaction->received_by !== 'System' ? $transaction->received_by : auth()->user()->full_name) }}">
                                @error('received_by')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-100 dark:border-slate-700">
                        <a href="{{ route('transactions.index') }}"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-500 rounded-lg text-sm font-medium text-gray-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:bg-slate-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Update Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
