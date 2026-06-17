<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Apple', 'Samsung', 'Xiaomi', 'Huawei',
            'Sony', 'LG', 'Lenovo', 'HP', 'Dell', 'Asus',
            'Logitech', 'Razer', 'HyperX', 'Corsair',
            'JBL', 'Bose', 'Sennheiser', 'Canon', 'Nikon',
        ];

        foreach ($brands as $name) {
            Brand::create([
                'name'      => $name,
                'slug'      => Str::slug($name),
                'is_active' => true,
            ]);
        }
    }
}