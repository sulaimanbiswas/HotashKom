<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Parent Product
        DB::table('products')->insert([
            'id' => 1,
            'parent_id' => null,
            'brand_id' => 1,
            'name' => 'Galaxy S26 Ultra',
            'slug' => 'galaxy-s26-ultra',
            'description' => 'Flagship device from Samsung.',
            'short_description' => '12GB RAM, 256GB Storage',
            'price' => 130000,
            'selling_price' => 125000,
            'suggested_price' => 128000,
            'average_purchase_price' => 105000,
            'wholesale' => json_encode(['5' => '120000', '10' => '115000']),
            'sku' => 'SAM-S26U',
            'should_track' => true,
            'stock_count' => 50,
            'desc_img' => false,
            'desc_img_pos' => 'bottom',
            'is_active' => true,
            'hot_sale' => true,
            'new_arrival' => true,
            'shipping_inside' => 60,
            'shipping_outside' => 120,
            'delivery_text' => '2-3 days delivery.',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Product Pivots
        DB::table('category_product')->insert([
            ['category_id' => 2, 'product_id' => 1, 'created_at' => now(), 'updated_at' => now()]
        ]);

        DB::table('image_product')->insert([
            ['image_id' => 3, 'product_id' => 1, 'img_type' => 'featured', 'order' => 1, 'created_at' => now(), 'updated_at' => now()]
        ]);
    }
}
