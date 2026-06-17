<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $celulares = Category::where('slug', 'celulares')->first();
        $laptops   = Category::where('slug', 'laptops')->first();
        $gaming    = Category::where('slug', 'gaming')->first();

        $apple   = Brand::where('slug', 'apple')->first();
        $samsung = Brand::where('slug', 'samsung')->first();
        $razer   = Brand::where('slug', 'razer')->first();
        $lenovo  = Brand::where('slug', 'lenovo')->first();

        $iphone = Product::create([
            'category_id' => $celulares->id,
            'brand_id'    => $apple->id,
            'sku'         => 'IPH16PRO',
            'name'        => 'iPhone 16 Pro',
            'slug'        => 'iphone-16-pro',
            'description' => 'El iPhone más avanzado con chip A18 Pro.',
            'price'       => 4999.00,
            'sale_price'  => 4799.00,
            'stock'       => 50,
            'is_active'   => true,
            'is_featured' => true,
        ]);

        foreach ([
            ['128 GB', 'IPH16PRO-128', 4999.00, 4799.00, 30],
            ['256 GB', 'IPH16PRO-256', 5499.00, 5299.00, 15],
            ['512 GB', 'IPH16PRO-512', 6299.00, null,    10],
        ] as [$name, $sku, $price, $sale, $stock]) {
            ProductVariant::create([
                'product_id' => $iphone->id,
                'sku'        => $sku,
                'name'       => $name,
                'price'      => $price,
                'sale_price' => $sale,
                'stock'      => $stock,
                'is_active'  => true,
            ]);
        }

        Product::create([
            'category_id' => $celulares->id,
            'brand_id'    => $samsung->id,
            'sku'         => 'SAMS25',
            'name'        => 'Samsung Galaxy S25',
            'slug'        => 'samsung-galaxy-s25',
            'description' => 'Potencia y elegancia en un solo dispositivo.',
            'price'       => 3999.00,
            'sale_price'  => 3699.00,
            'stock'       => 40,
            'is_active'   => true,
            'is_featured' => true,
        ]);

        Product::create([
            'category_id' => $gaming->id,
            'brand_id'    => $razer->id,
            'sku'         => 'RAZBWV4',
            'name'        => 'Razer BlackWidow V4',
            'slug'        => 'razer-blackwidow-v4',
            'description' => 'Teclado mecánico gaming con switches Razer Green.',
            'price'       => 599.00,
            'sale_price'  => null,
            'stock'       => 25,
            'is_active'   => true,
            'is_featured' => false,
        ]);

        $laptop = Product::create([
            'category_id' => $laptops->id,
            'brand_id'    => $lenovo->id,
            'sku'         => 'LENIP5',
            'name'        => 'Lenovo IdeaPad 5',
            'slug'        => 'lenovo-ideapad-5',
            'description' => 'Laptop para trabajo y estudio con pantalla Full HD.',
            'price'       => 2799.00,
            'sale_price'  => 2499.00,
            'stock'       => 20,
            'is_active'   => true,
            'is_featured' => true,
        ]);

        foreach ([
            ['8GB RAM / 512GB SSD',  'LENIP5-8-512',  2799.00, 2499.00, 12],
            ['16GB RAM / 512GB SSD', 'LENIP5-16-512', 3299.00, 2999.00, 8],
        ] as [$name, $sku, $price, $sale, $stock]) {
            ProductVariant::create([
                'product_id' => $laptop->id,
                'sku'        => $sku,
                'name'       => $name,
                'price'      => $price,
                'sale_price' => $sale,
                'stock'      => $stock,
                'is_active'  => true,
            ]);
        }
    }
}