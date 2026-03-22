<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insert Admin
        DB::table('users')->updateOrInsert(
            ['email' => 'rvsolution696@gmail.com'],
            [
                'company_id' => 1,
                'role_id' => 1,
                'name' => 'Admin User',
                'phone' => '9876543210',
                'email_verified_at' => now(),
                'password' => \Illuminate\Support\Facades\Hash::make('Rvsolution@1415'),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Insert Sales
        DB::table('users')->updateOrInsert(
            ['email' => 'sales@rvcrm.local'],
            [
                'company_id' => 1,
                'role_id' => 2,
                'name' => 'Sales User',
                'phone' => '9876543211',
                'email_verified_at' => now(),
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}