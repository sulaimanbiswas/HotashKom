<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('brands')->insert([
            ['id' => 1, 'name' => 'Samsung', 'slug' => 'samsung', 'image_id' => 1, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Apple', 'slug' => 'apple', 'image_id' => null, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
