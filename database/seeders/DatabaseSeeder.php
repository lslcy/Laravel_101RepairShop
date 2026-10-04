<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create an Admin User
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'Administrator',
            'status' => 'Active',
            'email_verified_at' => now(),
        ]);

        // Create a Technician User
        User::create([
            'first_name' => 'John',
            'last_name' => 'Tech',
            'username' => 'technician',
            'email' => 'tech@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'Technician',
            'status' => 'Active',
            'email_verified_at' => now(),
        ]);

        // Create a Secretary User
        User::create([
            'first_name' => 'Jane',
            'last_name' => 'Secretary',
            'username' => 'secretary',
            'email' => 'secretary@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'Secretary',
            'status' => 'Active',
            'email_verified_at' => now(),
        ]);

        // Create a Cashier User
        User::create([
            'first_name' => 'Mary',
            'last_name' => 'Cashier',
            'username' => 'cashier',
            'email' => 'cashier@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'Cashier',
            'status' => 'Active',
            'email_verified_at' => now(),
        ]);

        // Create Sample Customers
        $customer1 = \App\Models\Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address' => '123 Main St, Cityville',
            'phone_no' => '09171234567',
        ]);

        $customer2 = \App\Models\Customer::create([
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'address' => '456 Oak Ave, Townsburg',
            'phone_no' => '09187654321',
        ]);

        // Create Sample Inventory Parts
        \App\Models\Part::create([
            'part_no' => 'P-001',
            'name' => 'Compressor Motor',
            'price' => 2500.00,
            'quantity_stock' => 5,
        ]);

        \App\Models\Part::create([
            'part_no' => 'P-002',
            'name' => 'Fan Blade',
            'price' => 450.00,
            'quantity_stock' => 12,
        ]);

        // Create Sample Appliance
        $appliance = \App\Models\Appliance::create([
            'customer_id' => $customer1->id,
            'brand' => 'Samsung',
            'category' => 'Cooling',
            'product' => 'Refrigerator',
            'model_no' => 'SAM-REF-123',
            'date_in' => now(),
        ]);

        // Create Sample Service Report
        \App\Models\ServiceReport::create([
            'customer_name' => $customer1->first_name . ' ' . $customer1->last_name,
            'customer_id' => $customer1->id,
            'appliance_id' => $appliance->id,
            'date_in' => now(),
            'status' => 'Pending',
            'findings' => 'Cooling issue reported.',
        ]);
    }
}
