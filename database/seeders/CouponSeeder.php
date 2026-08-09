<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('coupons')->insert([
            'id' => 1,
            'code' => 'HOTASH50',
            'name' => '50 TK Discount',
            'description' => 'Flat 50 discount on any product',
            'coupon_type' => 'subscription',
            'discount' => 50.00,
            'discount_type' => 'fixed',
            'max_usages' => 100,
            'used_count' => 0,
            'expires_at' => Carbon::now()->addDays(30),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
