<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => null, 'name' => 'Electronics', 'slug' => 'electronics', 'order' => 1, 'image_id' => 2, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'parent_id' => 1, 'name' => 'Mobiles', 'slug' => 'mobiles', 'order' => 1, 'image_id' => null, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
