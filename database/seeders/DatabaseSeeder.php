<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create demo company
        $company = Company::create([
            'name' => 'Demo Company Pvt Ltd',
            'gstin' => '27AABCU9603R1ZM',
            'pan' => 'AABCU9603R',
            'phone' => '9876543210',
            'email' => 'admin@democompany.com',
            'address' => [
                'line1' => '123 Business Park',
                'line2' => 'Tech Hub',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
                'country' => 'India',
            ],
            'default_gst_percent' => 18,
            'quote_prefix' => 'Q',
            'quote_fy_format' => 'YY-YY',
            'terms_and_conditions' => "1. Prices are valid for 30 days from quote date.\n2. Payment terms: 50% advance, 50% on delivery.\n3. GST extra as applicable.\n4. Delivery: 7-10 working days from order confirmation.",
            'status' => 'active',
        ]);

        // Create Admin Role
        $adminRole = Role::create([
            'company_id' => $company->id,
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Full access to all features',
            'permissions' => Role::PERMISSIONS,
            'is_system' => true,
        ]);

        // Create Sales Role
        $salesRole = Role::create([
            'company_id' => $company->id,
            'name' => 'Sales',
            'slug' => 'sales',
            'description' => 'Access to leads, clients, quotes',
            'permissions' => [
                'leads.read',
                'leads.write',
                'clients.read',
                'clients.write',
                'quotes.read',
                'quotes.write',
                'products.read',
                'categories.read',
                'activities.read',
                'activities.write',
                'tasks.read',
                'tasks.write',
            ],
            'is_system' => true,
        ]);

        // Create Viewer Role
        Role::create([
            'company_id' => $company->id,
            'name' => 'Viewer',
            'slug' => 'viewer',
            'description' => 'Read-only access',
            'permissions' => [
                'leads.read',
                'clients.read',
                'quotes.read',
                'products.read',
                'categories.read',
                'activities.read',
                'tasks.read',
                'reports.read',
            ],
            'is_system' => false,
        ]);

        // Create Admin User
        User::create([
            'company_id' => $company->id,
            'role_id' => $adminRole->id,
            'name' => 'Admin User',
            'email' => 'admin@rvcrm.local',
            'phone' => '9876543210',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create Sales User
        User::create([
            'company_id' => $company->id,
            'role_id' => $salesRole->id,
            'name' => 'Sales User',
            'email' => 'sales@rvcrm.local',
            'phone' => '9876543211',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->command->info('Demo company, roles, and users created successfully!');
        $this->command->info('Admin login: admin@rvcrm.local / password123');
    }
}
