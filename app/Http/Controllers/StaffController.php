<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $staff = User::when($search, fn($q) => $q->where('first_name', 'ilike', "%$search%")
        ->orWhere('last_name', 'ilike', "%$search%")
        ->orWhere('username', 'ilike', "%$search%")
        ->orWhere('email', 'ilike', "%$search%"))
            ->latest()
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
        return view('staff.index', compact('staff', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('staff.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:' . User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'in:Administrator,Secretary,Technician,Cashier'],
            'status' => ['required', 'string', 'in:Active,Inactive'],
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hash the custom user provided password
            'role' => $request->role,
            'status' => $request->status,
        ]);

        return redirect()->route('staff.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $staff) // Using route model binding

    {
        return view('staff.edit', compact('staff'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $staff)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $staff->id],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $staff->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'in:Administrator,Secretary,Technician,Cashier'],
        ]);

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
        ];

        // Only update password if a new one was provided and the admin is editing their own profile
        if ($request->filled('password') && auth()->id() === $staff->id) {
            $data['password'] = Hash::make($request->password);
        }

        $staff->update($data);



        return redirect()->route('staff.index')->with('success', 'Staff member updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $staff)
    {
        if ($staff->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $newStatus = $staff->status === 'Active' ? 'Inactive' : 'Active';
        $staff->update(['status' => $newStatus]);

        return redirect()->route('staff.index')->with('success', "Staff member status updated to {$newStatus}.");
    }
}
