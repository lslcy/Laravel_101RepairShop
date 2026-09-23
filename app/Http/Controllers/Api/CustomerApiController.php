<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\ServiceReport;
use App\Models\Appliance;

class CustomerApiController extends Controller
{
    /**
     * Resolve current customer ID from request header or query param.
     * Defaults to customer ID 1 for quick testing.
     */
    private function resolveCustomerId(Request $request): int
    {
        return (int) ($request->header('X-Customer-ID') ?? $request->query('customer_id', 1));
    }

    /**
     * GET /api/customer/profile
     */
    public function getProfile(Request $request)
    {
        $customerId = $this->resolveCustomerId($request);
        $customer = Customer::find($customerId);

        if (!$customer) {
            // If customer 1 doesn't exist, get the first available customer
            $customer = Customer::first();
        }

        if (!$customer) {
            return response()->json([
                'id' => $customerId,
                'first_name' => 'Valued',
                'last_name' => 'Customer',
                'email' => 'customer@101repairshop.com',
                'phone_no' => '09123456789',
                'address' => 'Customer Address',
                'profile_picture' => null,
            ]);
        }

        return response()->json($customer);
    }

    /**
     * GET /api/customer/service-reports
     */
    public function getServiceReports(Request $request)
    {
        $customerId = $this->resolveCustomerId($request);

        // Fetch service reports for this customer, or return latest reports if customer has none
        $reports = ServiceReport::with('appliance')
            ->where('customer_id', $customerId)
            ->orderBy('id', 'desc')
            ->get();

        if ($reports->isEmpty()) {
            // Fallback for demonstration: load all recent service reports
            $reports = ServiceReport::with('appliance')
                ->orderBy('id', 'desc')
                ->limit(20)
                ->get();
        }

        return response()->json($reports);
    }

    /**
     * GET /api/customer/appliances
     */
    public function getAppliances(Request $request)
    {
        $customerId = $this->resolveCustomerId($request);

        $appliances = Appliance::where('customer_id', $customerId)->get();

        if ($appliances->isEmpty()) {
            $appliances = Appliance::limit(10)->get();
        }

        return response()->json($appliances);
    }

    /**
     * GET /api/customer/appointments
     */
    public function getAppointments(Request $request)
    {
        $customerId = $this->resolveCustomerId($request);

        // Map pending/scheduled service reports as appointments
        $serviceReports = ServiceReport::with('appliance')
            ->where(function ($q) use ($customerId) {
                $q->where('customer_id', $customerId)
                  ->orWhere('id', '>', 0);
            })
            ->whereIn('status', ['Pending', 'In Progress', 'For Diagnosis', 'Scheduled'])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $appointments = $serviceReports->map(function ($report) {
            $applianceName = $report->appliance 
                ? trim(($report->appliance->brand ?? '') . ' ' . ($report->appliance->product ?? ''))
                : 'General Appliance';

            return [
                'id' => $report->id,
                'customer_id' => $report->customer_id ?? 1,
                'title' => 'Repair Check-up #' . $report->id,
                'appliance_name' => $applianceName ?: 'Appliance Unit',
                'appointment_date' => $report->date_in ? $report->date_in->toIso8601String() : now()->addDays(1)->toIso8601String(),
                'time_slot' => '09:00 AM - 12:00 PM',
                'status' => $report->status,
                'notes' => $report->findings ?? $report->remarks ?? 'Diagnostic inspection',
            ];
        });

        return response()->json($appointments);
    }

    /**
     * POST /api/customer/appointments
     */
    public function createAppointment(Request $request)
    {
        $customerId = $this->resolveCustomerId($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'appliance_name' => 'nullable|string|max:255',
            'appointment_date' => 'required',
            'time_slot' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Automatically create a ServiceReport entry in the database with 'Pending' status!
        $report = ServiceReport::create([
            'customer_id' => $customerId,
            'customer_name' => Customer::find($customerId)?->first_name ?? 'Mobile Customer',
            'date_in' => $validated['appointment_date'],
            'status' => 'Pending',
            'findings' => $validated['notes'] ?? 'Booked via customer mobile app',
            'remarks' => 'Title: ' . $validated['title'] . ($validated['appliance_name'] ? ' (' . $validated['appliance_name'] . ')' : ''),
        ]);

        return response()->json([
            'id' => $report->id,
            'customer_id' => $customerId,
            'title' => $validated['title'],
            'appliance_name' => $validated['appliance_name'] ?? 'Appliance',
            'appointment_date' => $report->date_in ? $report->date_in->toIso8601String() : now()->toIso8601String(),
            'time_slot' => $validated['time_slot'] ?? '09:00 AM',
            'status' => 'Pending',
            'notes' => $validated['notes'] ?? '',
        ], 201);
    }
}