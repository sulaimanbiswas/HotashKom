<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        // Attributes
        DB::table('attributes')->insert([
            ['id' => 1, 'name' => 'Color', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Storage', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Options
        DB::table('options')->insert([
            ['id' => 1, 'attribute_id' => 1, 'name' => 'Phantom Black', 'value' => '#000', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'attribute_id' => 2, 'name' => '256 GB', 'value' => '256gb', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
