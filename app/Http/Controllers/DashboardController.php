<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Weekly Stats (Current Week)
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $weeklyCustomers = \App\Models\Customer::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
        $weeklyIncome = $this->receivedIncomeBetween($startOfWeek, $endOfWeek);
        $weeklyServices = \App\Models\ServiceReport::whereBetween('date_in', [$startOfWeek, $endOfWeek])->count();
        $pendingAppointments = \App\Models\Appointment::where('status', 'Pending')->count();

        // Previous Week Stats (for growth rate)
        $prevStart = now()->subWeek()->startOfWeek();
        $prevEnd = now()->subWeek()->endOfWeek();

        $prevCustomers = \App\Models\Customer::whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevIncome = $this->receivedIncomeBetween($prevStart, $prevEnd);
        $prevServices = \App\Models\ServiceReport::whereBetween('date_in', [$prevStart, $prevEnd])->count();

        // Calculate growth rates
        $customerGrowth = $prevCustomers > 0 ? round((($weeklyCustomers - $prevCustomers) / $prevCustomers) * 100) : ($weeklyCustomers > 0 ? 100 : 0);
        $incomeGrowth = $prevIncome > 0 ? round((($weeklyIncome - $prevIncome) / $prevIncome) * 100) : ($weeklyIncome > 0 ? 100 : 0);
        $serviceGrowth = $prevServices > 0 ? round((($weeklyServices - $prevServices) / $prevServices) * 100) : ($weeklyServices > 0 ? 100 : 0);

        // Low Stock Parts (Threshold < 10)
        $lowStockParts = \App\Models\Part::where('quantity_stock', '<', 10)
            ->latest()
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // Recent Services for activity feed
        $recentServices = \App\Models\ServiceReport::with(['customer', 'appliance'])
            ->latest()
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // Recent Transactions (Limit 5)
        $recentTransactions = \App\Models\Transaction::with(['report.customer'])
            ->latest()
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // --- Chart.js Data (Last 6 Months) ---
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
        $reports = \App\Models\ServiceReport::with('details')->where('date_in', '>=', $sixMonthsAgo)->get();

        $serviceCounts = [];
        $monthlyCounts = []; 
        $months = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('M');
            $months[] = $month;
            $monthlyCounts[$month] = [];
        }

        foreach ($reports as $report) {
            $month = \Carbon\Carbon::parse($report->date_in)->format('M');
            if (!isset($monthlyCounts[$month])) continue;

            $types = $report->details ? $report->details->service_types : [];
            if (empty($types) || !is_array($types)) {
                $types = ['Other'];
            }

            foreach ($types as $type) {
                if (!isset($serviceCounts[$type])) $serviceCounts[$type] = 0;
                $serviceCounts[$type]++;

                if (!isset($monthlyCounts[$month][$type])) $monthlyCounts[$month][$type] = 0;
                $monthlyCounts[$month][$type]++;
            }
        }

        arsort($serviceCounts);
        $topTypes = array_slice(array_keys($serviceCounts), 0, 3);
        if (empty($topTypes)) {
            $topTypes = ['Installation', 'Maintenance', 'Repair']; 
        }

        $donutLabels = [];
        $donutData = [];
        foreach ($topTypes as $type) {
            $donutLabels[] = $type;
            $donutData[] = $serviceCounts[$type] ?? 0;
        }

        $lineDatasets = [];
        $colors = ['#F97316', '#22C55E', '#3B82F6']; 
        $bgColors = ['rgba(249,115,22,0.08)', 'rgba(34,197,94,0.08)', 'rgba(59,130,246,0.08)'];
        
        foreach ($topTypes as $index => $type) {
            $data = [];
            foreach ($months as $month) {
                $data[] = $monthlyCounts[$month][$type] ?? 0;
            }
            $lineDatasets[] = [
                'label' => $type,
                'data' => $data,
                'borderColor' => $colors[$index % 3] ?? '#000',
                'backgroundColor' => $bgColors[$index % 3] ?? 'rgba(0,0,0,0)',
                'fill' => true,
                'tension' => 0.4,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
            ];
        }

        $chartData = [
            'donutLabels' => $donutLabels,
            'donutData' => $donutData,
            'lineMonths' => $months,
            'lineDatasets' => $lineDatasets
        ];

        return view('dashboard', compact(
            'weeklyCustomers',
            'weeklyIncome',
            'weeklyServices',
            'pendingAppointments',
            'lowStockParts',
            'recentTransactions',
            'recentServices',
            'customerGrowth',
            'incomeGrowth',
            'serviceGrowth',
            'chartData'
        ));
    }

    private function receivedIncomeBetween($start, $end): float
    {
        $transactions = \App\Models\Transaction::whereBetween('created_at', [$start, $end]);

        return (clone $transactions)->where('payment_status', 'Paid')->sum('total_amount')
            + (clone $transactions)->where('payment_status', 'Partial')->sum('partial_payment_amount');
    }
}
