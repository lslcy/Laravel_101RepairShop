<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ServiceDetail;
use App\Notifications\ServiceReportCreated;
use App\Notifications\ServiceReportUpdated;
use Illuminate\Support\Facades\Notification;

class ServiceReportController extends Controller
{
    private function getTechniciansWithAvailability()
    {
        $technicians = User::where('role', 'Technician')->get();

        // Mark technicians with any non-closed service as "Busy". Use account status "Inactive" as "Off-Duty".
        $busyNames = ServiceDetail::query()
            ->whereNotNull('technician')
            ->where('technician', '<>', '')
            ->whereHas('report', function ($q) {
                $q->whereNotIn('status', ['Completed', 'Cancelled', 'Canceled']);
            })
            ->pluck('technician')
            ->flatMap(function ($list) {
                return collect(explode(',', (string) $list))
                    ->map(fn($name) => trim($name))
                    ->filter();
            })
            ->map(fn($name) => strtolower($name))
            ->unique()
            ->values()
            ->all();

        $busyLookup = array_fill_keys($busyNames, true);

        foreach ($technicians as $tech) {
            $fullName = trim(($tech->first_name ?? '') . ' ' . ($tech->last_name ?? ''));
            $fullNameKey = strtolower($fullName);

            if (strtolower((string) $tech->status) === 'inactive') {
                $availability = 'Off-Duty';
            } elseif ($fullName !== '' && isset($busyLookup[$fullNameKey])) {
                $availability = 'Busy';
            } else {
                $availability = 'Available';
            }

            $tech->setAttribute('availability_status', $availability);
        }

        return $technicians;
    }

    private function checkServiceCreationAccess()
    {
        if (auth()->check() && !in_array(auth()->user()->role, ['Administrator', 'Secretary'])) {
            abort(403, 'Unauthorized. Only Secretaries and Administrators can create or delete Service Reports.');
        }
    }

    private function processParts(\App\Models\ServiceReport $report, $partsInput, $isNew = false)
    {
        if ($partsInput === null)
            return 0;

        $partsData = [];
        $partsTotalCost = 0;

        // Restore old stock if updating before wiping the pivot
        if (!$isNew) {
            foreach ($report->parts as $oldPart) {
                // Return stock visually back to inventory
                $oldPart->increment('quantity_stock', $oldPart->pivot->quantity);
            }
        }

        if (is_array($partsInput)) {
            foreach ($partsInput as $partItem) {
                if (isset($partItem['id']) && isset($partItem['quantity'])) {
                    $qty = (int) $partItem['quantity'];
                    $price = (float) str_replace(['₱', ','], '', $partItem['price']);

                    $partsData[$partItem['id']] = [
                        'quantity' => $qty,
                        'price' => $price,
                    ];
                    $partsTotalCost += ($qty * $price);

                    // Deduct new stock
                    $actualPart = \App\Models\Part::find($partItem['id']);
                    if ($actualPart) {
                        $actualPart->decrement('quantity_stock', $qty);
                    }
                }
            }
        }

        // Sync the pivot table with quantities & prices
        $report->parts()->sync($partsData);
        return $partsTotalCost;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $date = $request->input('date');

        $services = \App\Models\ServiceReport::with(['customer', 'appliance', 'details'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('customer_name', 'ilike', "%$search%")
                          ->orWhere('id', 'ilike', "%$search%");
                });
            })
            ->when($status, function ($q) use ($status) {
                if ($status === 'In Progress') {
                    $q->whereIn('status', \App\Models\ServiceReport::IN_PROGRESS_STATUSES);
                } else {
                    $q->where('status', $status);
                }
            })
            ->when($date, function ($q) use ($date) {
                $q->whereDate('date_in', $date);
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('services.index', compact('services', 'search', 'status', 'date'));
    }

    public function create()
    {
        $this->checkServiceCreationAccess();
        $customers = \App\Models\Customer::with('appliances')->get();
        $technicians = $this->getTechniciansWithAvailability();
        $parts = \App\Models\Part::all();
        $servicePrices = \App\Models\ServicePrice::all();
        return view('services.create', compact('customers', 'technicians', 'parts', 'servicePrices'));
    }

    public function store(Request $request)
    {
        $this->checkServiceCreationAccess();
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'appliance_id' => 'required|exists:appliances,id',
            'date_in' => 'required|date',
            'status' => ['required', 'string', \Illuminate\Validation\Rule::in(\App\Models\ServiceReport::STATUSES)],
            'findings' => 'nullable|string',
            'problem_desc' => 'required|string',
            'labor_cost' => 'nullable|numeric',
            'remarks' => 'nullable|string',
            'dealer' => 'nullable|string',
            'dop' => 'nullable|date',
            'technicians' => 'nullable|array|max:3',
            'service_types' => 'nullable|array',
            'used_parts' => 'nullable|string',
            'parts' => 'nullable|array',
            'miscellaneous_cost' => 'nullable|numeric',
        ]);

        $customer = \App\Models\Customer::find($validated['customer_id']);

        // Check for duplicate service report
        $exists = \App\Models\ServiceReport::where('customer_id', $customer->id)
            ->where('appliance_id', $validated['appliance_id'])
            ->where('date_in', $validated['date_in'])
            ->where('status', $validated['status'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'A duplicate service report already exists for this appliance and date.');
        }

        $validated['customer_name'] = trim($customer->first_name . ' ' . $customer->last_name);

        $report = \App\Models\ServiceReport::create($validated);

        // Process Parts & Inventory Sync
        $partsInput = $request->input('parts', []);
        $partsTotalCost = $this->processParts($report, $partsInput, true);

        // Create initial Service Detail
        $techs = isset($validated['technicians']) ? implode(', ', $validated['technicians']) : null;
        $labor = $request->labor_cost ?? 0;
        $miscCost = $request->miscellaneous_cost ?? 0;
        $totalAmount = $labor + $partsTotalCost + $miscCost;

        ServiceDetail::create([
            'report_id' => $report->id,
            'service_types' => $validated['service_types'] ?? [],
            'labor' => $labor,
            'parts_total_charge' => $partsTotalCost,
            'miscellaneous_cost' => $miscCost,
            'total_amount' => $totalAmount,
            'complaint' => $request->problem_desc,
            'technician' => $techs,
        ]);

        return redirect()->route('services.index')->with('success', 'Service Report created successfully.');
    }

    public function show(\App\Models\ServiceReport $service)
    {
        $service->load(['comments.user', 'transactions']);
        $technicians = $this->getTechniciansWithAvailability();
        $techStatusMap = $technicians->mapWithKeys(function (User $tech) {
            $name = strtolower(trim(($tech->first_name ?? '') . ' ' . ($tech->last_name ?? '')));
            return [$name => $tech->availability_status];
        });

        return view('services.show', compact('service', 'techStatusMap'));
    }

    public function edit(\App\Models\ServiceReport $service)
    {
        if (auth()->check() && auth()->user()->role === 'Cashier') {
            abort(403, 'Cashiers cannot edit Service Reports.');
        }
        $customers = \App\Models\Customer::with('appliances')->get();
        $technicians = $this->getTechniciansWithAvailability();
        $parts = \App\Models\Part::all();
        $servicePrices = \App\Models\ServicePrice::all();
        $service->load('parts');
        return view('services.edit', compact('service', 'customers', 'technicians', 'parts', 'servicePrices'));
    }

    public function update(Request $request, \App\Models\ServiceReport $service)
    {
        if (auth()->user()->role === 'Cashier') {
            abort(403, 'Cashiers cannot edit Service Reports.');
        }

        $userRole = auth()->user()->role;
        $rules = [
            'customer_id' => 'required|exists:customers,id',
            // Reports booked from the Flutter app may not be linked to an appliance yet.
            'appliance_id' => 'nullable|exists:appliances,id',
            'date_in' => 'required|date',
            'status' => ['required', 'string', \Illuminate\Validation\Rule::in(\App\Models\ServiceReport::STATUSES)],
            'findings' => 'nullable|string',
            'problem_desc' => 'required|string',
            'labor_cost' => 'nullable|numeric',
            'remarks' => 'nullable|string',
            'dealer' => 'nullable|string',
            'dop' => 'nullable|date',
            'technicians' => 'nullable|array|max:3',
            'service_types' => 'nullable|array',
            'used_parts' => 'nullable|string',
            'parts' => 'nullable|array',
            'miscellaneous_cost' => 'nullable|numeric',
        ];

        if ($userRole === 'Technician') {
            $ignores = ['customer_id', 'appliance_id', 'date_in', 'problem_desc', 'labor_cost', 'dealer', 'dop', 'technicians', 'service_types'];
            foreach ($ignores as $ignore)
                unset($rules[$ignore]);
        }

        $validated = $request->validate($rules);

        if (array_key_exists('appliance_id', $validated) && $validated['appliance_id'] === '') {
            $validated['appliance_id'] = null;
        }

        // Preserve missing attributes for disabled HTML form fields
        if ($userRole === 'Technician') {
            $validated['customer_id'] = $service->customer_id;
            $validated['appliance_id'] = $service->appliance_id;
            $validated['date_in'] = $service->date_in;
            $validated['problem_desc'] = $service->details?->complaint ?: (string) $service->findings;
            $validated['labor_cost'] = $service->details ? $service->details->labor : 0;
            $validated['dealer'] = $service->dealer;
            $validated['dop'] = $service->dop;
            $validated['service_types'] = $service->details ? $service->details->service_types : [];
            $validated['technicians'] = $service->details ? explode(', ', $service->details->technician) : [];
        }

        $customer = \App\Models\Customer::find($validated['customer_id']);
        $validated['customer_name'] = trim($customer->first_name . ' ' . $customer->last_name);

        $service->update($validated);

        // Process Parts & Inventory Sync
        $partsTotalCost = $service->details ? $service->details->parts_total_charge : 0;
        $partsInput = $request->input('parts', []);
        $partsTotalCost = $this->processParts($service, $partsInput, false);

        // Update or Create ServiceDetail
        $techs = isset($validated['technicians']) ? implode(', ', $validated['technicians']) : null;
        $labor = $request->labor_cost ?? ($service->details ? $service->details->labor : 0);
        $miscCost = $request->miscellaneous_cost ?? ($service->details ? $service->details->miscellaneous_cost : 0);
        $totalAmount = $labor + $partsTotalCost + $miscCost;

        ServiceDetail::updateOrCreate(
            ['report_id' => $service->id],
            [
                'complaint' => $validated['problem_desc'],
                'labor' => $labor,
                'parts_total_charge' => $partsTotalCost,
                'miscellaneous_cost' => $miscCost,
                'service_types' => $validated['service_types'] ?? [],
                'total_amount' => $totalAmount,
                'technician' => $techs,
            ]
        );

        // Trigger Notification to all users
        $users = User::all();
        Notification::send($users, new ServiceReportUpdated($service->id, $service->customer_name));

        return redirect()->route('services.index')->with('success', 'Service Report updated successfully.');
    }

    public function destroy(\App\Models\ServiceReport $service)
    {
        $this->checkServiceCreationAccess();
        // Bypass $fillable: deleted_by should not be user-input controlled.
        $service->forceFill(['deleted_by' => auth()->id()])->save();
        $service->delete();
        return redirect()->route('services.index')->with('success', 'Service Report deleted successfully.');
    }

    public function storeComment(Request $request, \App\Models\ServiceReport $service)
    {
        $request->validate([
            'comment_text' => 'required|string',
        ]);

        \App\Models\ServiceProgressComment::create([
            'report_id' => $service->id,
            'comment_text' => $request->comment_text,
            'created_by' => auth()->id(),
            'created_by_name' => auth()->user()->full_name, // Assuming full_name exists on User
            'progress_key' => 'update', // Default key
        ]);

        return back()->with('success', 'Comment added successfully.');
    }

    public function print(\App\Models\ServiceReport $service)
    {
        $service->load(['details', 'appliance', 'parts', 'transactions']);
        return view('services.print', compact('service'));
    }
}
