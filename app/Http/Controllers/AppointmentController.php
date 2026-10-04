<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Staff-side management of appointments booked from the Flutter mobile app.
 */
class AppointmentController extends Controller
{
    private function checkAppointmentAccess(): void
    {
        if (auth()->check() && !in_array(auth()->user()->role, ['Administrator', 'Secretary'])) {
            abort(403, 'Unauthorized. Only Secretaries and Administrators can manage Appointments.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAppointmentAccess();

        $search = $request->input('search');
        $status = $request->input('status');
        $date = $request->input('date');

        $appointments = Appointment::with('customer')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('title', 'ilike', "%$search%")
                        ->orWhere('appliance_name', 'ilike', "%$search%")
                        ->orWhere('notes', 'ilike', "%$search%")
                        ->orWhereHas('customer', function ($c) use ($search) {
                            $c->where('first_name', 'ilike', "%$search%")
                                ->orWhere('last_name', 'ilike', "%$search%")
                                ->orWhere('phone_no', 'ilike', "%$search%");
                        });
                });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($date, fn ($q) => $q->whereDate('appointment_date', $date))
            // Upcoming/pending first, then most recent.
            ->orderByRaw("CASE WHEN status = 'Pending' THEN 0 WHEN status = 'Confirmed' THEN 1 ELSE 2 END")
            ->orderBy('appointment_date', 'asc')
            ->paginate(25)
            ->withQueryString();

        $counts = Appointment::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('appointments.index', compact('appointments', 'search', 'status', 'date', 'counts'));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $this->checkAppointmentAccess();

        $validated = $request->validate([
            'status' => ['required', Rule::in(Appointment::STATUSES)],
            'appointment_date' => 'nullable|date',
            'time_slot' => 'nullable|string|max:100',
        ]);

        $appointment->update(array_filter($validated, fn ($v) => $v !== null));
        cache()->forget('sidebar_pending_appointments');

        return back()->with('success', "Appointment #{$appointment->id} marked as {$appointment->status}.");
    }

    /**
     * Open the "New Service Report" form pre-filled from an appointment.
     */
    public function convert(Appointment $appointment)
    {
        $this->checkAppointmentAccess();

        if ($appointment->status === 'Pending') {
            $appointment->update(['status' => 'Confirmed']);
            cache()->forget('sidebar_pending_appointments');
        }

        return redirect()
            ->route('services.create')
            ->withInput([
                'customer_id' => $appointment->customer_id,
                'date_in' => $appointment->appointment_date?->format('Y-m-d'),
                'status' => 'Pending',
                'problem_desc' => trim($appointment->title . ($appointment->appliance_name ? " ({$appointment->appliance_name})" : '') . ($appointment->notes ? "\n" . $appointment->notes : '')),
                'remarks' => "From mobile appointment #{$appointment->id}" . ($appointment->time_slot ? " ({$appointment->time_slot})" : ''),
            ])
            ->with('success', 'Service report form pre-filled from the appointment. Select the appliance and save.');
    }
}
