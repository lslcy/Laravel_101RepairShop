<x-app-layout>
    <div class="space-y-6">

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Weekly Customers -->
            <a href="{{ route('customers.index') }}"
                class="block bg-white dark:bg-slate-800 p-6 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm flex flex-col justify-between transition-colors">
                <div class="flex justify-between items-start">
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg text-blue-600 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $customerGrowth >= 0 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                        {{ $customerGrowth >= 0 ? '+' : '' }}{{ $customerGrowth }}%
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $weeklyCustomers }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Weekly Customers</p>
                </div>
            </a>

            <!-- Weekly Income -->
            <a href="{{ route('transactions.index') }}"
                class="block bg-white dark:bg-slate-800 p-6 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm flex flex-col justify-between transition-colors">
                <div class="flex justify-between items-start">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg text-green-700 dark:text-green-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $incomeGrowth >= 0 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                        {{ $incomeGrowth >= 0 ? '+' : '' }}{{ $incomeGrowth }}%
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-2xl font-semibold text-gray-900 dark:text-white">₱{{ number_format($weeklyIncome, 2) }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Weekly Service Income</p>
                </div>
            </a>

            <!-- Weekly Services -->
            <a href="{{ route('services.index') }}"
                class="block bg-white dark:bg-slate-800 p-6 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm flex flex-col justify-between transition-colors">
                <div class="flex justify-between items-start">
                    <div
                        class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg text-purple-700 dark:text-purple-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $serviceGrowth >= 0 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                        {{ $serviceGrowth >= 0 ? '+' : '' }}{{ $serviceGrowth }}%
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $weeklyServices }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Weekly Total Services</p>
                </div>
            </a>

            <!-- Growth Rate -->
            <a href="{{ route('transactions.index') }}"
                class="block bg-white dark:bg-slate-800 p-6 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm flex flex-col justify-between transition-colors">
                <div class="flex justify-between items-start">
                    <div
                        class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg text-orange-700 dark:text-orange-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $overallGrowth >= 0 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                        {{ $overallGrowth >= 0 ? '+' : '' }}{{ $overallGrowth }}%
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $overallGrowth >= 0 ? '+' : '' }}{{ $overallGrowth }}%</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Growth Rate</p>
                </div>
            </a>
        </div>

        <!-- Charts & Activity Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Popular Service Types (flip card) -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm transition-colors"
                x-data="{ flipped: false }">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Popular Service Types</h3>
                    <!-- Flip toggle button -->
                    <button @click="flipped = !flipped"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 dark:border-slate-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-700 hover:border-blue-300 transition-all"
                        :title="flipped ? 'Switch to Donut Chart' : 'Switch to Line Chart'">
                        <svg class="w-4 h-4 transition-transform duration-500" :class="flipped ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span x-text="flipped ? 'Donut' : 'Line'"></span>
                    </button>
                </div>

                <!-- Flip card wrapper -->
                <div class="relative" style="perspective: 1000px; height: 320px;">
                    <div class="w-full h-full absolute transition-all duration-700"
                        :style="flipped ? 'transform: rotateY(180deg); transform-style: preserve-3d;' : 'transform: rotateY(0deg); transform-style: preserve-3d;'"
                        style="transform-style: preserve-3d;">

                        <!-- FRONT: Donut Chart -->
                        <div class="absolute inset-0 flex flex-col"
                            style="backface-visibility: hidden; -webkit-backface-visibility: hidden;">
                            <div class="flex-1 flex items-center justify-center relative">
                                <canvas id="serviceTypesChart"></canvas>
                                <div
                                    class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                    <span class="text-2xl font-semibold text-gray-900 dark:text-white"
                                        id="popularServicePercentage">
                                        @if(array_sum($chartData['donutData']) > 0)
                                            {{ round(($chartData['donutData'][0] / array_sum($chartData['donutData'])) * 100) }}%
                                        @else
                                            0%
                                        @endif
                                    </span>
                                    <span
                                        class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider"
                                        id="popularServiceLabel">
                                        {{ strtoupper($chartData['donutLabels'][0] ?? 'N/A') }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2 flex justify-center space-x-6">
                                @php $chartColors = ['bg-orange-500', 'bg-green-500', 'bg-blue-500']; @endphp
                                @foreach($chartData['donutLabels'] as $index => $label)
                                    <div class="flex items-center"><span
                                            class="w-3 h-3 rounded-full {{ $chartColors[$index % 3] }} mr-2"></span><span
                                            class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</span></div>
                                @endforeach
                            </div>
                        </div>

                        <!-- BACK: Line Chart -->
                        <div class="absolute inset-0 flex flex-col"
                            style="backface-visibility: hidden; -webkit-backface-visibility: hidden; transform: rotateY(180deg);">
                            <div class="flex-1 flex items-center justify-center">
                                <canvas id="serviceTypesLineChart"></canvas>
                            </div>
                            <p class="text-center text-xs text-gray-400 dark:text-gray-500 pb-2 mt-1">Service type
                                trends over the last 6 months</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div
                class="bg-white dark:bg-slate-800 p-6 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm transition-colors flex flex-col gap-6">
                <div class="min-h-[48px] flex items-center justify-between">
                    <div class="flex flex-col gap-1">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Recent Activity
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Latest service updates
                        </p>
                    </div>
                    <a href="{{ route('services.index') }}"
                        class="inline-flex items-center gap-2 text-xs font-semibold text-blue-600 dark:text-blue-400">
                        View All
                        <img class="w-4 h-4 dark:invert dark:opacity-75" alt=""
                            src="{{ asset('assets/icons/arrow-right-blue.svg') }}" />
                    </a>
                </div>

                <div class="flex flex-col gap-4">
                    @if(count($recentServices) > 0)
                        @foreach($recentServices as $service)
                            @php
                                $status = strtolower($service->status ?? '');
                                if (str_contains($status, 'complete')) {
                                    $pillBg = 'bg-green-50 dark:bg-green-900/30';
                                    $pillText = 'text-green-700 dark:text-green-400';
                                } elseif (str_contains($status, 'wait')) {
                                    $pillBg = 'bg-amber-50 dark:bg-orange-900/30';
                                    $pillText = 'text-amber-700 dark:text-orange-400';
                                } else {
                                    $pillBg = 'bg-blue-50 dark:bg-blue-900/30';
                                    $pillText = 'text-blue-700 dark:text-blue-400';
                                }
                                $titleCustomer = $service->customer_name ?: 'Unknown Customer';
                                $titleAppliance = $service->appliance_name ?? optional($service->appliance)->name ?? 'Service';
                            @endphp
                            <div class="relative min-h-[68px]">
                                <div
                                    class="relative h-full {{ $loop->last ? '' : 'border-l-2 border-[#e5e7eb] dark:border-slate-700' }}">
                                    <div class="absolute left-[28px] top-0  flex flex-col gap-2">
                                        <div class="">
                                            <p
                                                class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                {{ $titleCustomer }} - {{ $titleAppliance }}
                                            </p>
                                        </div>
                                        <div class=" flex items-center gap-2">
                                            <span
                                                class=" px-2.5 py-1 rounded-full inline-flex items-center justify-center {{ $pillBg }}">
                                                <span class="text-xs font-semibold {{ $pillText }}">
                                                    {{ $service->status ?? 'Unknown' }}
                                                </span>
                                            </span>
                                            <span
                                                class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                {{ optional($service->created_at)->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                    <span
                                        class="absolute -left-2 top-0 w-4 h-4 rounded-full bg-blue-500 border-4 border-white dark:border-slate-800"></span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-xs text-gray-500 dark:text-gray-400">No recent activity.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Dashboard Information -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Low Stock Parts -->
        <div
            class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-6 flex flex-col gap-4 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Low Stock Alerts</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Inventory items needing restock</p>
                </div>
                @if(Route::has('parts.index'))
                    <a href="{{ route('parts.index') }}"
                        class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">View Inventory</a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700/50">
                    <thead>
                        <tr>
                            <th
                                class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider py-2">
                                Part Name</th>
                            <th
                                class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider py-2">
                                Stock</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700/50">
                        @forelse($lowStockParts ?? collect() as $part)
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $part->name }}</td>
                                <td class="py-3 text-sm text-right text-red-600 font-bold">
                                    {{ $part->quantity_stock }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">Inventory
                                    levels are healthy.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div
            class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-6 flex flex-col gap-4 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Transactions</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Latest paid and unpaid invoices</p>
                </div>
                @if(Route::has('transactions.index'))
                    <a href="{{ route('transactions.index') }}"
                        class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700/50">
                    <thead>
                        <tr>
                            <th
                                class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider py-2">
                                Customer</th>
                            <th
                                class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider py-2">
                                Amount</th>
                            <th
                                class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider py-2">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700/50">
                        @forelse($recentTransactions ?? collect() as $transaction)
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ optional($transaction->report)->customer_name ?? 'Unknown' }}</td>
                                <td class="py-3 text-sm text-gray-900 dark:text-gray-300">
                                    ₱{{ number_format($transaction->total_amount, 2) }}</td>
                                <td class="py-3 text-sm text-right">
                                    @php
                                        $statusClass = match ($transaction->payment_status) {
                                            'Paid' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border dark:border-green-800/50',
                                            'Unpaid' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border dark:border-red-800/50',
                                            'Partial' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border dark:border-yellow-800/50',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-slate-300 border dark:border-slate-600',
                                        };
                                    @endphp
                                    <span
                                        class="px-2 py-1 text-[10px] font-semibold rounded-full {{ $statusClass }}">{{ $transaction->payment_status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">No recent
                                    transactions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <!-- Chart.js Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- DONUT CHART ---
            const ctx = document.getElementById('serviceTypesChart');
            if (ctx) {
                const donutLabels = @json($chartData['donutLabels']);
                const donutData = @json($chartData['donutData']);
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: donutLabels,
                        datasets: [{
                            data: donutData,
                            backgroundColor: ['#F97316', '#22C55E', '#3B82F6'],
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return context.label + ': ' + context.raw + '%';
                                    }
                                }
                            }
                        },
                        cutout: '75%',
                        onHover: function (event, activeElements) {
                            if (activeElements.length > 0) {
                                const index = activeElements[0].index;
                                const value = this.data.datasets[0].data[index];
                                const label = this.data.labels[index];
                                const total = donutData.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                document.getElementById('popularServicePercentage').textContent = percentage + '%';
                                document.getElementById('popularServiceLabel').textContent = label ? label.toUpperCase() : '';
                            }
                        }
                    }
                });
            }

            // --- LINE CHART ---
            const ctxLine = document.getElementById('serviceTypesLineChart');
            if (ctxLine) {
                const lineMonths = @json($chartData['lineMonths']);
                const lineDatasets = @json($chartData['lineDatasets']);
                new Chart(ctxLine, {
                    type: 'line',
                    data: {
                        labels: lineMonths,
                        datasets: lineDatasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    boxWidth: 10,
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0,0,0,0.04)'
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>