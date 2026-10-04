<?php

namespace App\Http\Controllers;

use App\Models\Appliance;
use App\Models\Customer;
use Illuminate\Http\Request;

class ApplianceController extends Controller
{
    public function store(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'product' => 'nullable|string|max:255', // Appliance Type
            'model_no' => [
                'nullable',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('appliances', 'model_no')->whereNull('deleted_at')
            ],
            'serial_no' => [
                'nullable',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('appliances', 'serial_no')->whereNull('deleted_at')
            ],
            'date_in' => 'nullable|date',
            'appliance_size' => 'nullable|in:Small,Medium,Large',
            'status' => 'nullable|string|max:50',
            'warranty_end' => 'nullable|date',
        ]);

        $validated['customer_id'] = $customer->id;
        $validated['status'] = $validated['status'] ?? 'Active';

        Appliance::create($validated);

        return back()->with('success', 'Appliance added successfully.');
    }

    public function update(Request $request, Appliance $appliance)
    {
        $validated = $request->validate([
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'product' => 'nullable|string|max:255', // Appliance Type
            'model_no' => [
                'nullable',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('appliances', 'model_no')->ignore($appliance->id)->whereNull('deleted_at')
            ],
            'serial_no' => [
                'nullable',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('appliances', 'serial_no')->ignore($appliance->id)->whereNull('deleted_at')
            ],
            'date_in' => 'nullable|date',
            'appliance_size' => 'nullable|in:Small,Medium,Large',
            'status' => 'nullable|string|max:50',
            'warranty_end' => 'nullable|date',
        ]);

        $appliance->update($validated);

        return back()->with('success', 'Appliance updated successfully.');
    }

    public function destroy(Appliance $appliance)
    {
        $appliance->delete();
        return back()->with('success', 'Appliance removed.');
    }
}
