<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Http\Request;

/**
 * Customer-facing API for the Flutter app.
 *
 * Every route is behind AuthenticateSupabaseCustomer, so the customer always
 * comes from the verified Supabase token and never from a header or query
 * string. Results are strictly scoped to that customer, and Eloquent
 * soft-delete scopes hide records archived in the web admin.
 */
class CustomerApiController extends Controller
{
    private function customer(Request $request): Customer
    {
        return $request->attributes->get('customer');
    }

    /**
     * GET /api/customer/profile
     */
    public function getProfile(Request $request)
    {
        $customer = $this->customer($request);

        $data = $customer->toArray();
        // Web uploads are stored as "storage/..." on the Laravel server; give the app a full URL.
        if ($customer->profile_picture && !str_starts_with($customer->profile_picture, 'http')) {
            $data['profile_picture'] = asset($customer->profile_picture);
        }

        return response()->json($data);
    }

    /**
     * GET /api/customer/appliances
     */
    public function getAppliances(Request $request)
    {
        return response()->json(
            $this->customer($request)->appliances()->orderByDesc('id')->get()
        );
    }

    /**
     * GET /api/customer/service-reports
     * Includes cost/technician details (service_details) and payments.
     */
    public function getServiceReports(Request $request)
    {
        $reports = $this->customer($request)
            ->serviceReports()
            ->with(['appliance', 'details', 'transactions', 'comments:id,report_id,progress_key,comment_text,created_by_name,created_at'])
            ->orderByDesc('id')
            ->get();

        return response()->json($reports);
    }

    /**
     * GET /api/customer/transactions
     */
    public function getTransactions(Request $request)
    {
        return response()->json(
            $this->customer($request)->transactions()->with('report:id,status,appliance_id')->orderByDesc('id')->get()
        );
    }

    /**
     * GET /api/customer/appointments
     */
    public function getAppointments(Request $request)
    {
        return response()->json(
            $this->customer($request)->appointments()->orderByDesc('appointment_date')->get()
        );
    }

    /**
     * POST /api/customer/appointments
     */
    public function createAppointment(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'appliance_name' => 'nullable|string|max:255',
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);

        $appointment = $this->customer($request)->appointments()->create($validated + ['status' => 'Pending']);

        return response()->json($appointment->fresh(), 201);
    }

    /**
     * PATCH /api/customer/appointments/{appointment}/cancel
     */
    public function cancelAppointment(Request $request, int $appointment)
    {
        $model = Appointment::where('customer_id', $this->customer($request)->id)->findOrFail($appointment);

        if (in_array($model->status, ['Completed', 'Cancelled'], true)) {
            return response()->json(['message' => "Appointment is already {$model->status}."], 422);
        }

        $model->update(['status' => 'Cancelled']);

        return response()->json($model);
    }
}
