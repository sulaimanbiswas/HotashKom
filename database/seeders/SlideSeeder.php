<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SlideSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('slides')->insert([
            'id' => 1,
            'mobile_src' => 'slides/mobile1.jpg',
            'desktop_src' => 'slides/desktop1.jpg',
            'title' => 'Big Winter Sale',
            'text' => 'Up to 50% Off on Electronics',
            'btn_name' => 'Shop Now',
            'btn_href' => '/shop',
            'is_active' => true,
            'object_fit' => 'cover',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
