<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@hotashkom.com',
            'phone_number' => '01700000000',
            'address' => 'Dhaka, Bangladesh',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'domain' => null,
            'order_prefix' => 'HK',
            'is_active' => true,
            'db_host' => null,
            'db_name' => null,
            'db_username' => null,
            'db_password' => null,
            'logo' => 'defaults/admin-logo.png',
            'inside_dhaka_shipping' => 0,
            'outside_dhaka_shipping' => 0,
        ]);

        User::create([
            'name' => 'Anik Trader',
            'email' => 'anik@hotashkom.com',
            'phone_number' => '01811111111',
            'address' => 'Jalesharitola, Bogura',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'domain' => 'null',
            'order_prefix' => 'AT80',
            'is_active' => true,
            'db_host' => '127.0.0.1',
            'db_name' => 'hotashkom_anik',
            'db_username' => 'root',
            'db_password' => '',
            'logo' => 'logos/anik_trader.png',
            'inside_dhaka_shipping' => 60,
            'outside_dhaka_shipping' => 120,
        ]);

        User::create([
            'name' => 'Khan Enterprise',
            'email' => 'khan@hotashkom.com',
            'phone_number' => '01922222222',
            'address' => 'Motijheel, Dhaka',
            'email_verified_at' => null,
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'domain' => 'null',
            'order_prefix' => 'KE',
            'is_active' => false,
            'db_host' => '127.0.0.1',
            'db_name' => 'hotashkom_khan',
            'db_username' => 'root',
            'db_password' => '',
            'logo' => null,
            'inside_dhaka_shipping' => 70,
            'outside_dhaka_shipping' => 130,
        ]);

        User::create([
            'name' => 'Rahim Uddin',
            'email' => 'rahim@gmail.com',
            'phone_number' => '01533333333',
            'address' => 'Mirpur 10, Dhaka',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'domain' => null,
            'order_prefix' => null,
            'is_active' => true,
            'db_host' => null,
            'db_name' => null,
            'db_username' => null,
            'db_password' => null,
            'logo' => null,
            'inside_dhaka_shipping' => 0,
            'outside_dhaka_shipping' => 0,
        ]);

        User::create([
            'name' => 'Karim Mia',
            'email' => null,
            'phone_number' => '01644444444',
            'address' => 'Kushtia, Bangladesh',
            'email_verified_at' => null,
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'domain' => null,
            'order_prefix' => null,
            'is_active' => true,
            'db_host' => null,
            'db_name' => null,
            'db_username' => null,
            'db_password' => null,
            'logo' => null,
            'inside_dhaka_shipping' => 0,
            'outside_dhaka_shipping' => 0,
        ]);
    }
}
