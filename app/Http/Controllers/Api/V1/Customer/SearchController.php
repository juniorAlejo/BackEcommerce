<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = $request->get('q', '');

        if (strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $products = Product::with(['brand', 'images' => fn($q) => $q->where('position', 0)])
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
                'price'         => $p->price,
                'sale_price'    => $p->sale_price,
                'current_price' => $p->sale_price ?? $p->price,
                'image'         => $p->images->first()?->url,
                'brand'         => $p->brand?->name,
            ]);

        return response()->json(['data' => $products]);
    }
}