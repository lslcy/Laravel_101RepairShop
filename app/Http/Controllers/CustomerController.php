<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

    public function store(CustomerRequest $request)
    {
        $this->checkCustomerAccess();
        $validated = $request->validated();

        // Only persist validated fields (never mass-assign raw request input).
        $data = collect($validated)->except('profile_picture')->all();

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('customer-profiles', 'public');
            $data['profile_picture'] = 'storage/' . $path;
        }

        try {
            \App\Models\Customer::create($data);
        } catch (UniqueConstraintViolationException $e) {
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            $this->duplicateIdentity($e);
        }

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

    public function update(CustomerRequest $request, \App\Models\Customer $customer)
    {
        $this->checkCustomerAccess();
        $validated = $request->validated();

        $data = collect($validated)->except('profile_picture')->all();

        $oldPath = null;
        if ($request->hasFile('profile_picture')) {
            if ($customer->profile_picture && !str_starts_with($customer->profile_picture, 'http')) {
                $oldPath = str_replace('storage/', '', $customer->profile_picture);
            }
            $path = $request->file('profile_picture')->store('customer-profiles', 'public');
            $data['profile_picture'] = 'storage/' . $path;
        }

        try {
            $customer->update($data);
        } catch (UniqueConstraintViolationException $e) {
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            $this->duplicateIdentity($e);
        }

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    private function duplicateIdentity(UniqueConstraintViolationException $exception): never
    {
        // A concurrent request may pass validation before another request saves the same identity.
        $messages = [
            'customers_identity_name_unique' => ['last_name', 'A customer with this full name already exists. Use the existing customer record.'],
            'customers_identity_email_unique' => ['email', 'This email address is already used by a customer.'],
            'customers_identity_phone_unique' => ['phone_no', 'This phone number is already used by a customer.'],
            'customers_identity_auth_unique' => ['auth_id', 'This mobile account is already linked to another customer.'],
        ];
        foreach ($messages as $constraint => [$field, $message]) {
            if (str_contains($exception->getMessage(), $constraint) || str_contains($exception->getMessage(), explode('. ', $message)[0])) {
                throw ValidationException::withMessages([$field => $message]);
            }
        }

        throw $exception;
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
