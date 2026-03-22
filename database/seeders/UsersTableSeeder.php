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
        // DB::table('users')->delete();
        
        $users = array (
  0 => 
  array (
    'id' => 1,
    'company_id' => 1,
    'role_id' => 1,
    'name' => 'Admin User',
    'email' => 'rvsolution696@gmail.com',
    'phone' => '9876543210',
    'email_verified_at' => '2026-03-10 15:57:22',
    'password' => '$2y$12$/TTjrFAfmMLkpwZ5zzIyxepYtaP.nUgEAAUuX/MNuXe99565A7ooK',
    'avatar' => NULL,
    'status' => 'active',
    'last_login_at' => NULL,
    'remember_token' => NULL,
    'created_at' => '2026-03-10 15:57:22',
    'updated_at' => '2026-03-10 15:57:22',
    'deleted_at' => NULL,
  ),
  1 => 
  array (
    'id' => 2,
    'company_id' => 1,
    'role_id' => 2,
    'name' => 'Sales User',
    'email' => 'sales@rvcrm.local',
    'phone' => '9876543211',
    'email_verified_at' => '2026-03-10 15:57:23',
    'password' => '$2y$12$.SVNUXTq2S8475k4f6UfKOiyQDPK9WG6EJPT0LQnlnrVJhRJzAYqK',
    'avatar' => NULL,
    'status' => 'active',
    'last_login_at' => NULL,
    'remember_token' => NULL,
    'created_at' => '2026-03-10 15:57:23',
    'updated_at' => '2026-03-10 15:57:23',
    'deleted_at' => NULL,
  ),
);
        
        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['id' => $user['id']],
                $user
            );
        }
    }
}