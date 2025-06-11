<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Coca Cola 600ml',
                'external_sku' => 'COCA-600',
                'price' => 25.50,
            ],
            [
                'name' => 'Cerveza Corona 355ml',
                'external_sku' => 'CORONA-355',
                'price' => 35.00,
            ],
            [
                'name' => 'Agua Mineral 1L',
                'external_sku' => 'AGUA-1L',
                'price' => 18.00,
            ],
            [
                'name' => 'Jugo de Naranja 500ml',
                'external_sku' => 'JUGO-NAR-500',
                'price' => 22.75,
            ],
            [
                'name' => 'Café Americano',
                'external_sku' => 'CAFE-AMER',
                'price' => 45.00,
            ],
        ];

        foreach ($products as $productData) {
            Product::firstOrCreate(
                ['external_sku' => $productData['external_sku']],
                $productData
            );
        }
    }
}
