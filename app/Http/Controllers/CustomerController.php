<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    private function checkCustomerAccess()
    {
        if (auth()->check() && !in_array(auth()->user()->role, ['Administrator', 'Secretary'])) {
            abort(403, 'Unauthorized. Only Secretaries and Administrators can manage Customers.');
        }
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $hasAppliances = $request->input('has_appliances');

        $customers = \App\Models\Customer::with('appliances')
            ->when($search, function ($q) use ($search) {
            $q->where(function ($query) use ($search) {
                    $query->where('first_name', 'ilike', "%$search%")
                        ->orWhere('last_name', 'ilike', "%$search%")
                        ->orWhere('phone_no', 'ilike', "%$search%");
                }
                );
            })
            ->when($hasAppliances, function ($q) use ($hasAppliances) {
            if ($hasAppliances === 'yes') {
                $q->has('appliances');
            }
            elseif ($hasAppliances === 'no') {
                $q->doesntHave('appliances');
            }
        })
            ->latest()
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search', 'hasAppliances'));
    }

    public function create()
    {
        $this->checkCustomerAccess();
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $this->checkCustomerAccess();
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => [
                'nullable', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = \App\Models\Customer::where('first_name', $request->first_name)
                        ->where('last_name', $value)
                        ->exists();
                    if ($exists) {
                        $fail('A customer with the same first and last name already exists.');
                    }
                },
            ],
            'address' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone_no' => 'required|numeric|digits_between:7,15',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'phone_no.required' => 'The phone number is required.',
            'phone_no.numeric' => 'The phone number must contain only numbers.',
            'phone_no.digits_between' => 'The phone number must be between 7 and 15 digits.',
        ]);

        // Only persist validated fields (never mass-assign raw request input).
        $data = collect($validated)->except('profile_picture')->all();

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('customer-profiles', 'public');
            $data['profile_picture'] = 'storage/' . $path;
        }

        \App\Models\Customer::create($data);

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(\App\Models\Customer $customer)
    {
        $customer->load([
            'appliances' => fn ($q) => $q->latest()->orderByDesc('id'),
            'serviceReports' => fn ($q) => $q->latest()->orderByDesc('id')->with('appliance'),
        ]);
        return view('customers.show', compact('customer'));
    }

    public function edit(\App\Models\Customer $customer)
    {
        $this->checkCustomerAccess();
        $customer->load(['appliances' => fn ($q) => $q->latest()->orderByDesc('id')]);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, \App\Models\Customer $customer)
    {
        $this->checkCustomerAccess();
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => [
                'nullable', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($request, $customer) {
                    $exists = \App\Models\Customer::where('first_name', $request->first_name)
                        ->where('last_name', $value)
                        ->where('id', '!=', $customer->id)
                        ->exists();
                    if ($exists) {
                        $fail('A customer with the same first and last name already exists.');
                    }
                },
            ],
            'address' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            // Supabase auth.users id of the customer's mobile account.
            'auth_id' => ['nullable', 'uuid', Rule::unique('customers', 'auth_id')->ignore($customer->id)],
            'phone_no' => 'required|numeric|digits_between:7,15',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'phone_no.required' => 'The phone number is required.',
            'phone_no.numeric' => 'The phone number must contain only numbers.',
            'phone_no.digits_between' => 'The phone number must be between 7 and 15 digits.',
            'auth_id.uuid' => 'The mobile account ID must be a valid UUID from Supabase Auth.',
            'auth_id.unique' => 'This mobile account is already linked to another customer.',
        ]);

        $data = collect($validated)->except('profile_picture')->all();

        if ($request->hasFile('profile_picture')) {
            if ($customer->profile_picture && !str_starts_with($customer->profile_picture, 'http')) {
                $oldPath = str_replace('storage/', '', $customer->profile_picture);
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                }
            }
            $path = $request->file('profile_picture')->store('customer-profiles', 'public');
            $data['profile_picture'] = 'storage/' . $path;
        }

        $customer->update($data);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(\App\Models\Customer $customer)
    {
        $this->checkCustomerAccess();
        // Bypass $fillable: deleted_by should not be user-input controlled.
        $customer->forceFill(['deleted_by' => auth()->id()])->save();
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
}
