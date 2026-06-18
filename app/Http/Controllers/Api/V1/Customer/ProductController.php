<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheKeys;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cacheKey = CacheKeys::PRODUCTS_PREFIX . md5($request->fullUrl());

        $result = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = Product::select(['id', 'category_id', 'brand_id', 'sku', 'name', 'slug', 'price', 'sale_price', 'stock', 'is_active', 'is_featured', 'created_at'])
                ->with([
                    'category:id,name,slug',
                    'brand:id,name,slug,logo',
                    'images' => fn($q) => $q->select(['id', 'product_id', 'url', 'alt', 'position'])->where('position', 0)
                ])
                ->where('is_active', true);

            if ($request->filled('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->category)
                      ->orWhereHas('parent', fn($p) => $p->where('slug', $request->category));
                });
            }

            if ($request->filled('brand')) {
                $query->whereHas('brand', fn($q) => $q->where('slug', $request->brand));
            }

            if ($request->filled('featured')) {
                $query->where('is_featured', true);
            }

            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(fn($q) => $q
                    ->where('name', 'ilike', "%{$s}%")
                    ->orWhere('sku', 'ilike', "%{$s}%")
                );
            }

            $sort = $request->get('sort', 'created_at_desc');
            match($sort) {
                'price_asc'  => $query->orderBy('price', 'asc'),
                'price_desc' => $query->orderBy('price', 'desc'),
                'name_asc'   => $query->orderBy('name', 'asc'),
                default      => $query->orderBy('created_at', 'desc'),
            };

            $products = $query->paginate($request->get('per_page', 20))->toArray();

            return [
                'data' => $products['data'],
                'meta' => [
                    'current_page' => $products['current_page'],
                    'last_page'    => $products['last_page'],
                    'per_page'     => $products['per_page'],
                    'total'        => $products['total'],
                ],
            ];
        });

        return response()->json($result);
    }

    public function show(string $slug): JsonResponse
{
    $cacheKey = CacheKeys::PRODUCTS_PREFIX . "slug:{$slug}";

    $product = Cache::remember($cacheKey, 300, function () use ($slug) {
        return Product::with(['category.parent', 'brand', 'images', 'variants'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail()
            ->toArray();
    });

    return response()->json(['data' => $product]);
}

    public function categories(): JsonResponse
{
    $categories = Cache::remember(CacheKeys::CATEGORIES, 600, function () {
        return \App\Models\Category::with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    });

    return response()->json(['data' => $categories]);
}

    public function brands(): JsonResponse
{
    $brands = Cache::remember(CacheKeys::BRANDS, 600, function () {
        return \App\Models\Brand::where('is_active', true)->orderBy('name')->get()->toArray();
    });

    return response()->json(['data' => $brands]);
}
    public function brandsByCategory(Request $request): JsonResponse
    {
        $slug     = $request->get('category', '');
        $cacheKey = CacheKeys::BRANDS_BY_CAT . ($slug ?: 'all');

        $result = Cache::remember($cacheKey, 600, function () use ($slug) {
            $ids = [];

            if ($slug) {
                $cat = \App\Models\Category::where('slug', $slug)->first();
                if ($cat) {
                    $ids = \App\Models\Category::where('id', $cat->id)
                        ->orWhere('parent_id', $cat->id)
                        ->pluck('id')
                        ->toArray();
                }
            }

            $brandIds = \App\Models\Product::where('is_active', true)
                ->when(!empty($ids), fn($q) => $q->whereIn('category_id', $ids))
                ->pluck('brand_id')
                ->unique()
                ->filter()
                ->toArray();

            $brands = \App\Models\Brand::whereIn('id', $brandIds)
                ->where('is_active', true)
                ->withCount(['products' => function ($q) use ($ids) {
                    $q->where('is_active', true)
                      ->when(!empty($ids), fn($q2) => $q2->whereIn('category_id', $ids));
                }])
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'logo', 'is_active']);

            return $brands->map(function ($brand) {
                return [
                    'id'            => $brand->id,
                    'name'          => $brand->name,
                    'slug'          => $brand->slug,
                    'logo'          => $brand->logo,
                    'is_active'     => $brand->is_active,
                    'product_count' => $brand->products_count,
                ];
            })->all();
        });

        return response()->json(['data' => $result]);
    }
}