<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = $request->get('q', '');

        if (strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $cacheKey = 'search:' . md5($q);

        $products = Cache::remember($cacheKey, 300, function () use ($q) {
            return Product::with(['brand', 'category', 'images' => fn($imgQuery) => $imgQuery->where('position', 0)])
                ->where('is_active', true)
                ->where(function ($query) use ($q) {
                    $query->where('name', 'ilike', "%{$q}%")
                          ->orWhere('sku', 'ilike', "%{$q}%")
                          ->orWhereHas('brand', fn($b) => $b->where('name', 'ilike', "%{$q}%"))
                          ->orWhereHas('category', fn($c) => $c->where('name', 'ilike', "%{$q}%"));
                })
                ->limit(10)
                ->get()
                ->map(fn($p) => [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'slug'          => $p->slug,
                    'sku'           => $p->sku,
                    'price'         => (float) $p->price,
                    'sale_price'    => $p->sale_price ? (float) $p->sale_price : null,
                    'current_price' => (float) ($p->sale_price ?? $p->price),
                    'brand'         => $p->brand ? [
                        'id'   => $p->brand->id,
                        'name' => $p->brand->name,
                        'slug' => $p->brand->slug,
                        'logo' => $p->brand->logo,
                    ] : null,
                    'category'      => $p->category ? [
                        'id'   => $p->category->id,
                        'name' => $p->category->name,
                        'slug' => $p->category->slug,
                    ] : null,
                    'images'        => $p->images->map(fn($img) => [
                        'id'       => $img->id,
                        'url'      => $img->url,
                        'alt'      => $img->alt,
                        'position' => $img->position,
                    ])->all(),
                ])
                ->all();
        });

        return response()->json(['data' => $products]);
    }
}