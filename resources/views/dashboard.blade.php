<x-app-layout>
    @php
        $canManageAppointments = in_array(auth()->user()->role, ['Administrator', 'Secretary'], true);
        $canManagePayments = in_array(auth()->user()->role, ['Administrator', 'Cashier'], true);
        $chartTotal = array_sum($chartData['donutData']);
        $chartColors = ['bg-blue-600', 'bg-amber-500', 'bg-emerald-500'];
        $metrics = [
            [
                'key' => 'customers', 'label' => 'New customers', 'value' => number_format($weeklyCustomers),
                'description' => 'This week', 'growth' => $customerGrowth, 'href' => route('customers.index'),
                'tone' => 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
                'paths' => ['M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
            ],
            [
                'key' => 'income', 'label' => 'Service income', 'value' => '₱' . number_format($weeklyIncome, 2),
                'description' => 'Collected on this week\'s bills', 'growth' => $incomeGrowth,
                'href' => $canManagePayments ? route('transactions.index') : null,
                'tone' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
                'paths' => ['M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ],
            [
                'key' => 'services', 'label' => 'Service reports', 'value' => number_format($weeklyServices),
                'description' => 'This week', 'growth' => $serviceGrowth, 'href' => route('services.index'),
                'tone' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400',
                'paths' => ['M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2', 'M9 3h6v4H9V3zm0 9h6m-6 4h6'],
            ],
            [
                'key' => 'appointments', 'label' => 'Pending appointments', 'value' => number_format($pendingAppointments),
                'description' => 'Awaiting confirmation', 'growth' => null,
                'href' => $canManageAppointments ? route('appointments.index', ['status' => 'Pending']) : null,
                'tone' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
                'paths' => ['M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z', 'M12 13v3l2 1'],
            ],
        ];
    @endphp

    <div class="ui-page">
        <div class="ui-page-header">
            <div>
                <h2 class="ui-page-title">Dashboard</h2>
            </div>
            @if($canManageAppointments)
                <a href="{{ route('services.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900">
                    <svg class="h-4 w-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14" /></svg>
                    New service report
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Shop overview">
            @foreach($metrics as $metric)
                @php $metricTag = $metric['href'] ? 'a' : 'div'; @endphp
                <{{ $metricTag }} @if($metric['href']) href="{{ $metric['href'] }}" @endif data-dashboard-metric="{{ $metric['key'] }}"
                    class="ui-card min-w-0 p-5 transition-colors {{ $metric['href'] ? 'group hover:border-blue-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:hover:border-blue-700' : '' }}">
                    <div class="flex items-center justify-between gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $metric['tone'] }}">
                            <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @foreach($metric['paths'] as $path)<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $path }}" />@endforeach
                            </svg>
                        </span>
                        @if($metric['growth'] !== null)
                            <span class="inline-flex items-center gap-1 rounded-md border px-2 py-1 text-xs font-medium {{ $metric['growth'] >= 0 ? 'border-emerald-200 text-emerald-600 dark:border-emerald-800 dark:text-emerald-400' : 'border-red-200 text-red-600 dark:border-red-800 dark:text-red-400' }}" aria-label="{{ $metric['growth'] }} percent change from last week">
                                <svg class="h-3 w-3" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $metric['growth'] >= 0 ? 'M7 14l5-5 5 5' : 'M7 10l5 5 5-5' }}" /></svg>
                                {{ $metric['growth'] > 0 ? '+' : '' }}{{ $metric['growth'] }}%
                            </span>
                        @elseif($metric['href'])
                            <svg class="h-4 w-4 text-gray-400 transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17L17 7M7 7h10v10" /></svg>
                        @endif
                    </div>
                    <p class="mt-4 text-sm font-medium text-gray-600 dark:text-slate-300">{{ $metric['label'] }}</p>
                    <p class="mt-1 break-words text-3xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums" data-metric-value>{{ $metric['value'] }}</p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-slate-400">
                        {{ $metric['description'] }}
                        @if($metric['growth'] !== null)
                            <span class="mx-1.5 text-gray-300 dark:text-slate-600">·</span>vs last week
                        @endif
                    </p>
                </{{ $metricTag }}>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
            <section class="ui-card min-w-0 p-5 sm:p-6 xl:col-span-3" aria-labelledby="service-overview-title" x-data="{ chartView: 'breakdown' }">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 id="service-overview-title" class="text-base font-semibold text-gray-900 dark:text-white">Service overview</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Most requested service types over the last six months</p>
                    </div>
                    @if($chartTotal > 0)
                        <div class="inline-flex self-start rounded-lg bg-gray-100 p-1 dark:bg-slate-900" aria-label="Chart view">
                            <button type="button" @click="chartView = 'breakdown'; $nextTick(() => window.dispatchEvent(new Event('dashboard-chart-change')))" :aria-pressed="chartView === 'breakdown'"
                                :class="chartView === 'breakdown' ? 'bg-white text-gray-900 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white'"
                                class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" id="dashboard-breakdown-button">Breakdown</button>
                            <button type="button" @click="chartView = 'trend'; $nextTick(() => window.dispatchEvent(new Event('dashboard-chart-change')))" :aria-pressed="chartView === 'trend'"
                                :class="chartView === 'trend' ? 'bg-white text-gray-900 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white'"
                                class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" id="dashboard-trend-button">Monthly trend</button>
                        </div>
                    @endif
                </div>
                @if($chartTotal > 0)
                    <div x-show="chartView === 'breakdown'" id="dashboard-breakdown" class="mt-6 grid items-center gap-6 sm:grid-cols-2">
                        <div class="relative mx-auto h-56 w-56 max-w-full">
                            <canvas id="serviceTypesChart" role="img" aria-label="Service type breakdown">@foreach($chartData['donutLabels'] as $index => $label){{ $label }}: {{ $chartData['donutData'][$index] }}. @endforeach</canvas>
                            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center px-12 text-center">
                                <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white" id="popularServicePercentage">{{ round(($chartData['donutData'][0] / $chartTotal) * 100) }}%</span>
                                <span class="mt-1 w-full break-words text-xs font-medium text-gray-500 dark:text-slate-400" id="popularServiceLabel">{{ $chartData['donutLabels'][0] }}</span>
                            </div>
                        </div>
                        <div class="space-y-5">
                            @foreach($chartData['donutLabels'] as $index => $label)
                                @php $percentage = round(($chartData['donutData'][$index] / $chartTotal) * 100); @endphp
                                <div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <div class="flex min-w-0 items-center gap-2"><span class="h-2.5 w-2.5 shrink-0 rounded-sm {{ $chartColors[$index % 3] }}" aria-hidden="true"></span><span class="break-words font-medium text-gray-700 dark:text-slate-200">{{ $label }}</span></div>
                                        <span class="shrink-0 font-semibold text-gray-900 dark:text-white tabular-nums">{{ $chartData['donutData'][$index] }}</span>
                                    </div>
                                    <div class="mt-2 h-1.5 overflow-hidden rounded bg-gray-100 dark:bg-slate-700"><div class="h-full rounded {{ $chartColors[$index % 3] }}" style="width: {{ $percentage }}%"></div></div>
                                    <p class="mt-1.5 text-xs text-gray-500 dark:text-slate-400">{{ $percentage }}% of the top service types</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div x-show="chartView === 'trend'" x-cloak id="dashboard-trend" class="mt-6">
                        <div class="relative h-64 sm:h-72"><canvas id="serviceTypesLineChart" role="img" aria-label="Monthly service type trends over the last six months">Monthly service trends for {{ implode(', ', $chartData['lineMonths']) }}.</canvas></div>
                        <div class="mt-4 flex flex-wrap justify-center gap-x-5 gap-y-2">
                            @foreach($chartData['donutLabels'] as $index => $label)<span class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-slate-300"><span class="h-2.5 w-2.5 rounded-sm {{ $chartColors[$index % 3] }}" aria-hidden="true"></span>{{ $label }}</span>@endforeach
                        </div>
                    </div>
                @else
                    <div class="flex min-h-[260px] flex-col items-center justify-center px-4 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-50 text-gray-400 dark:bg-slate-700/50 dark:text-slate-500"><svg class="h-6 w-6" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3v18h18M7 14l4-4 4 2 5-7" /></svg></span>
                        <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">No service data yet</p>
                        <p class="mt-1 max-w-xs text-sm text-gray-500 dark:text-slate-400">Your service breakdown and monthly trends will appear here as reports are added.</p>
                    </div>
                @endif
            </section>

            <section class="ui-card min-w-0 xl:col-span-2" aria-labelledby="recent-services-title">
                <div class="flex items-start justify-between gap-3 p-5 pb-4 sm:p-6 sm:pb-4">
                    <div><h3 id="recent-services-title" class="text-base font-semibold text-gray-900 dark:text-white">Recent service reports</h3><p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Latest reports and their current status</p></div>
                    <a href="{{ route('services.index') }}" class="shrink-0 rounded text-xs font-semibold text-blue-600 hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-400">View all</a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-slate-700/70">
                    @forelse($recentServices as $service)
                        @php
                            $customerName = $service->customer_name ?: 'Unknown customer';
                            $applianceLabel = $service->appliance_name ?: ($service->appliance?->product ?: 'Appliance service');
                        @endphp
                        <a href="{{ route('services.show', $service) }}" class="group flex items-start gap-3 px-5 py-4 transition-colors hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 dark:hover:bg-slate-700/40 sm:px-6">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400"><svg class="h-4 w-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 3h6v4H9V3zm0 9h6m-6 4h6" /></svg></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2"><p class="truncate text-sm font-semibold text-gray-900 group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400" title="{{ $customerName }}">{{ $customerName }}</p><span class="shrink-0 text-xs text-gray-400 dark:text-slate-500">#{{ $service->id }}</span></div>
                                <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-slate-400" title="{{ $applianceLabel }}">{{ $applianceLabel }}@if($service->appliance?->brand) · {{ $service->appliance->brand }}@endif</p>
                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2"><x-status-badge :status="$service->status" /><time class="text-xs text-gray-500 dark:text-slate-400" datetime="{{ $service->created_at?->toIso8601String() }}">{{ $service->created_at?->diffForHumans() }}</time></div>
                            </div>
                        </a>
                    @empty
                        <div class="flex min-h-[260px] flex-col items-center justify-center px-6 text-center"><p class="text-sm font-medium text-gray-900 dark:text-white">No recent service reports</p><p class="mt-1 max-w-xs text-sm text-gray-500 dark:text-slate-400">New reports will appear here so you can follow their progress.</p></div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="ui-card min-w-0 overflow-hidden" aria-labelledby="stock-alerts-title">
                <div class="flex items-start justify-between gap-3 p-5 sm:p-6">
                    <div><h3 id="stock-alerts-title" class="text-base font-semibold text-gray-900 dark:text-white">Low stock alerts</h3><p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Parts with fewer than 10 items remaining</p></div>
                    <a href="{{ route('inventory.index') }}" class="shrink-0 rounded text-xs font-semibold text-blue-600 hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-400">View inventory</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="ui-table">
                        <thead><tr><th scope="col" class="px-5 py-3 text-left sm:px-6">Part</th><th scope="col" class="px-4 py-3 text-right">Stock</th><th scope="col" class="px-5 py-3 text-right sm:px-6">Status</th></tr></thead>
                        <tbody>
                            @forelse($lowStockParts as $part)
                                <tr>
                                    <td class="px-5 py-4 sm:px-6"><p class="text-sm font-medium text-gray-900 dark:text-white">{{ $part->name }}</p><p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">{{ $part->part_no }}</p></td>
                                    <td class="px-4 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white tabular-nums">{{ $part->quantity_stock }}</td>
                                    <td class="px-5 py-4 text-right sm:px-6"><x-status-badge :status="$part->quantity_stock == 0 ? 'Out of Stock' : ($part->quantity_stock < 5 ? 'Critical' : 'Low Stock')" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-10 text-center"><p class="text-sm font-medium text-gray-900 dark:text-white">Stock levels look good</p><p class="mt-1 text-sm text-gray-500 dark:text-slate-400">There are no parts below the restock threshold.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="ui-card min-w-0 overflow-hidden" aria-labelledby="recent-transactions-title">
                <div class="flex items-start justify-between gap-3 p-5 sm:p-6">
                    <div><h3 id="recent-transactions-title" class="text-base font-semibold text-gray-900 dark:text-white">Recent transactions</h3><p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Newest bills and payment records</p></div>
                    @if($canManagePayments)<a href="{{ route('transactions.index') }}" class="shrink-0 rounded text-xs font-semibold text-blue-600 hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-400">View all</a>@endif
                </div>
                <div class="overflow-x-auto">
                    <table class="ui-table">
                        <thead><tr><th scope="col" class="px-5 py-3 text-left sm:px-6">Customer</th><th scope="col" class="px-4 py-3 text-right">Amount</th><th scope="col" class="px-5 py-3 text-right sm:px-6">Status</th></tr></thead>
                        <tbody>
                            @forelse($recentTransactions as $transaction)
                                <tr>
                                    <td class="px-5 py-4 sm:px-6">
                                        @if($canManagePayments)<a href="{{ route('transactions.show', $transaction) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-400">{{ $transaction->report?->customer_name ?: 'Unknown customer' }}</a>@else<span class="text-sm font-medium text-gray-900 dark:text-white">{{ $transaction->report?->customer_name ?: 'Unknown customer' }}</span>@endif
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">Transaction #{{ $transaction->id }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white tabular-nums">₱{{ number_format($transaction->total_amount, 2) }}</td>
                                    <td class="px-5 py-4 text-right sm:px-6"><x-status-badge :status="$transaction->payment_status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-10 text-center"><p class="text-sm font-medium text-gray-900 dark:text-white">No transactions yet</p><p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Bills and recorded payments will appear here.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined' || !document.getElementById('serviceTypesChart')) return;

            const data = @json($chartData);
            const colors = ['#2563eb', '#f59e0b', '#10b981'];
            const total = data.donutData.reduce((sum, value) => sum + Number(value), 0);
            const percentage = document.getElementById('popularServicePercentage');
            const label = document.getElementById('popularServiceLabel');
            const setPopularType = index => {
                percentage.textContent = (total ? Math.round(data.donutData[index] / total * 100) : 0) + '%';
                label.textContent = data.donutLabels[index] || '';
            };
            const donut = new Chart(document.getElementById('serviceTypesChart'), {
                type: 'doughnut',
                data: { labels: data.donutLabels, datasets: [{ data: data.donutData, backgroundColor: colors, borderWidth: 0, hoverOffset: 5 }] },
                options: {
                    responsive: false, maintainAspectRatio: false, animation: false, cutout: '78%',
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => `${context.label}: ${context.raw} (${Math.round(context.raw / total * 100)}%)` } } },
                    onHover: (event, activeElements) => setPopularType(activeElements.length ? activeElements[0].index : 0),
                },
            });
            const line = new Chart(document.getElementById('serviceTypesLineChart'), {
                type: 'line',
                data: { labels: data.lineMonths, datasets: data.lineDatasets.map((dataset, index) => ({ ...dataset, borderColor: colors[index % colors.length], backgroundColor: colors[index % colors.length] + '12', borderWidth: 2, pointRadius: 3, fill: true })) },
                options: {
                    responsive: false, maintainAspectRatio: false, animation: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } } }, x: { grid: { display: false }, ticks: { font: { size: 11 } } } },
                },
            });
            const updateTheme = () => {
                const dark = document.documentElement.classList.contains('dark');
                line.options.scales.x.ticks.color = dark ? '#94a3b8' : '#64748b';
                line.options.scales.y.ticks.color = dark ? '#94a3b8' : '#64748b';
                line.options.scales.y.grid.color = dark ? 'rgba(148,163,184,0.12)' : 'rgba(148,163,184,0.16)';
                line.options.scales.x.border.color = dark ? '#334155' : '#e2e8f0';
                line.options.scales.y.border.color = 'transparent';
                line.update('none');
                donut.update('none');
            };
            // Explicit CSS dimensions also keep charts sharp under the app's desktop zoom.
            const resizeCharts = () => {
                [donut, line].forEach(chart => {
                    const parent = chart.canvas.parentElement;
                    if (parent.clientWidth && parent.clientHeight) chart.resize(parent.clientWidth, parent.clientHeight);
                });
            };
            resizeCharts();
            const chartResizeObserver = new ResizeObserver(resizeCharts);
            chartResizeObserver.observe(donut.canvas.parentElement);
            chartResizeObserver.observe(line.canvas.parentElement);
            updateTheme();
            new MutationObserver(updateTheme).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            window.addEventListener('dashboard-chart-change', () => requestAnimationFrame(resizeCharts));
        });
    </script>
</x-app-layout>
