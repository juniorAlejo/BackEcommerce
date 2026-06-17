<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            ['name' => 'Tecnología', 'children' => [
                'Laptops', 'Celulares', 'Gaming', 'Accesorios',
            ]],
            ['name' => 'Audio', 'children' => [
                'Auriculares', 'Parlantes', 'Micrófonos',
            ]],
            ['name' => 'Fotografía', 'children' => [
                'Cámaras', 'Lentes', 'Trípodes',
            ]],
        ];

        foreach ($tree as $order => $item) {
            $parent = Category::create([
                'name'       => $item['name'],
                'slug'       => Str::slug($item['name']),
                'is_active'  => true,
                'sort_order' => $order,
            ]);

            foreach ($item['children'] as $i => $child) {
                Category::create([
                    'parent_id'  => $parent->id,
                    'name'       => $child,
                    'slug'       => Str::slug($child),
                    'is_active'  => true,
                    'sort_order' => $i,
                ]);
            }
        }
    }
}