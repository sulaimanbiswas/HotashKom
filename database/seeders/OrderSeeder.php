<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('orders')->insert([
            'id' => 1,
            'admin_id' => 2, // Assigned to Salesman
            'user_id' => 2,  // Ordered by General Customer
            'type' => 1,     // Order::ONLINE
            'name' => 'General Customer',
            'phone' => '01922222222',
            'email' => 'customer@gmail.com',
            'address' => 'Banani, Dhaka',
            'status' => 'PACKAGING',
            'status_at' => now(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Galaxy S26 Ultra', 'price' => 125000, 'quantity' => 1]
            ]),
            'note' => 'Please deliver fast.',
            'data' => json_encode(['payment' => 'COD', 'courier' => 'Pathao']),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('order_notes')->insert([
            'order_id' => 1,
            'admin_id' => 1,
            'note' => 'Order verified successfully.',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
