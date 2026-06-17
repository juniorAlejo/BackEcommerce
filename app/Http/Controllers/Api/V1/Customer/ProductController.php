<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
{
    $query = Product::with(['category', 'brand', 'images'])
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

    $products = $query->paginate($request->get('per_page', 20));

    return response()->json([
        'data' => $products->items(),
        'meta' => [
            'current_page' => $products->currentPage(),
            'last_page'    => $products->lastPage(),
            'per_page'     => $products->perPage(),
            'total'        => $products->total(),
        ],
    ]);
}

    public function show(string $slug): JsonResponse
    {
        $product = Product::with(['category.parent', 'brand', 'images', 'variants'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json(['data' => $product]);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function brands(): JsonResponse
    {
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        return response()->json(['data' => $brands]);
    }
    public function brandsByCategory(Request $request): JsonResponse
{
    $slug = $request->get('category', '');
    $ids  = [];

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
        ->orderBy('name')
        ->get(['id', 'name', 'slug', 'logo', 'is_active']);

    // Agregar conteo simple
    $result = $brands->map(function ($brand) use ($ids) {
        $count = \App\Models\Product::where('brand_id', $brand->id)
            ->where('is_active', true)
            ->when(!empty($ids), fn($q) => $q->whereIn('category_id', $ids))
            ->count();

        return [
            'id'            => $brand->id,
            'name'          => $brand->name,
            'slug'          => $brand->slug,
            'logo'          => $brand->logo,
            'is_active'     => $brand->is_active,
            'product_count' => $count,
        ];
    });

    return response()->json(['data' => $result]);
}
}