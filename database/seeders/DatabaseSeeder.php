<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Independent & Base Tables First
            ImageSeeder::class,
            AdminSeeder::class,
            UserSeeder::class,

            // 2. Tables dependent on Images
            BrandSeeder::class,
            CategorySeeder::class,

            // 3. Product ecosystem
            AttributeSeeder::class,
            ProductSeeder::class,

            // 4. Standalone settings/features
            CouponSeeder::class,
            SlideSeeder::class,

            // 5. Dependent on Users, Admins, and Products
            OrderSeeder::class,
        ]);
    }
}
