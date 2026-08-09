<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::create([
            'name' => 'Main Super Admin',
            'email' => 'superadmin@hotashkom.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role_id' => 1,
            'is_active' => true,
            'last_order_received_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        Admin::create([
            'name' => 'Sulaiman Sales',
            'email' => 'sales1@hotashkom.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role_id' => defined('App\Models\Admin::SALESMAN') ? Admin::SALESMAN : 2,
            'is_active' => true,
            'last_order_received_at' => now()->subHours(2),
            'remember_token' => Str::random(10),
        ]);

        Admin::create([
            'name' => 'Rahat Salesman',
            'email' => 'sales2@hotashkom.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role_id' => defined('App\Models\Admin::SALESMAN') ? Admin::SALESMAN : 2,
            'is_active' => true,
            'last_order_received_at' => now()->subMinutes(30),
            'remember_token' => Str::random(10),
        ]);

        Admin::create([
            'name' => 'Banned Staff',
            'email' => 'banned@hotashkom.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role_id' => defined('App\Models\Admin::SALESMAN') ? Admin::SALESMAN : 2,
            'is_active' => false,
            'last_order_received_at' => now()->subDays(5),
            'remember_token' => Str::random(10),
        ]);
    }
}
