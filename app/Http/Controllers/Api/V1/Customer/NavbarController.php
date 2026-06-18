<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheKeys;

class NavbarController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Cache::remember(CacheKeys::NAVBAR, 600, function () {
            return Category::with('children')
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(function ($category) {
                    $categoryIds = $category->children->pluck('id')->push($category->id);

                    $brands = Brand::whereHas('products', function ($q) use ($categoryIds) {
                        $q->whereIn('category_id', $categoryIds)->where('is_active', true);
                    })->where('is_active', true)->get(['id', 'name', 'slug', 'logo'])
                    ->toArray();

                    return [
                        'id'       => $category->id,
                        'name'     => $category->name,
                        'slug'     => $category->slug,
                        'image'    => $category->image,
                        'children' => $category->children->map(fn($c) => [
                            'id'   => $c->id,
                            'name' => $c->name,
                            'slug' => $c->slug,
                        ])->all(),
                        'brands' => $brands,
                    ];
                })
                ->all();
        });

        return response()->json(['data' => $categories]);
    }
}