<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('images')->insert([
            ['id' => 1, 'filename' => 'samsung-logo.jpg', 'disk' => 'public', 'path' => 'brands/samsung.jpg', 'extension' => 'jpg', 'mime' => 'image/jpeg', 'size' => '20480', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'filename' => 'electronics.jpg', 'disk' => 'public', 'path' => 'categories/electronics.jpg', 'extension' => 'jpg', 'mime' => 'image/jpeg', 'size' => '45000', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'filename' => 'phone-front.jpg', 'disk' => 'public', 'path' => 'products/phone-front.jpg', 'extension' => 'jpg', 'mime' => 'image/jpeg', 'size' => '102400', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
