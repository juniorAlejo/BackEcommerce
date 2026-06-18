<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ImageProcessor;
use App\Services\CacheKeys;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::with(['category', 'brand', 'images'])
            ->when($request->search, fn($q) => $q->where('name', 'ilike', "%{$request->search}%"))
            ->when($request->category, fn($q) => $q->where('category_id', $request->category))
            ->when($request->brand, fn($q) => $q->where('brand_id', $request->brand))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'sku'         => 'required|string|unique:products,sku',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'brand_id'    => 'required|exists:brands,id',
            'price'       => 'required|numeric|min:0',
            'sale_price'  => 'nullable|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'is_active'   => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ]);

        $data['is_active']   = $data['is_active'] ?? true;
        $data['is_featured'] = $data['is_featured'] ?? false;
        $data['slug']        = Str::slug($data['name']) . '-' . Str::lower(Str::random(5));

        $product = Product::create($data);

        CacheKeys::flushProducts();

        return response()->json([
            'data' => $product
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load([
            'category.parent',
            'brand',
            'images',
            'variants'
        ]);

        return response()->json([
            'data' => $product
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'sku'         => 'sometimes|string|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'category_id' => 'sometimes|exists:categories,id',
            'brand_id'    => 'sometimes|exists:brands,id',
            'price'       => 'sometimes|numeric|min:0',
            'sale_price'  => 'nullable|numeric|min:0',
            'stock'       => 'sometimes|integer|min:0',
            'is_active'   => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ]);

        $product->update($data);

        CacheKeys::flushProducts();

        return response()->json([
            'data' => $product
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        foreach ($product->images as $image) {
            $path = str_replace(asset('storage/') . '/', '', $image->url);
            Storage::disk('public')->delete($path);
            $image->delete();
        }

        $product->variants()->delete();
        $product->delete();

        CacheKeys::flushProducts();

        return response()->json([
            'message' => 'Producto eliminado.'
        ]);
    }

    public function toggleFeatured(Product $product): JsonResponse
    {
        $product->update([
            'is_featured' => !$product->is_featured
        ]);

        CacheKeys::flushProducts();

        return response()->json([
            'data' => [
                'is_featured' => $product->is_featured
            ]
        ]);
    }

    /**
     * Subir una o varias imágenes nuevas para el producto.
     */
    public function storeImages(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:8192',
        ]);

        if (!$request->hasFile('images')) {
            return response()->json([
                'message' => 'No se encontraron imágenes.'
            ], 422);
        }

        $images = [];
        $currentPosition = $product->images()->max('position') ?? -1;

        try {

            foreach ($request->file('images', []) as $file) {

                $path = ImageProcessor::processAndStore($file, 'products');

                $images[] = $product->images()->create([
                    'url'      => asset("storage/{$path}"),
                    'position' => ++$currentPosition,
                ]);
            }

            CacheKeys::flushProducts();

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Error al procesar imágenes: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'data' => $images
        ], 201);
    }

    /**
     * Reemplazar una imagen existente.
     */
    public function replaceImage(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:8192',
        ]);

        try {

            $oldPath = str_replace(asset('storage/') . '/', '', $image->url);

            Storage::disk('public')->delete($oldPath);

            $newPath = ImageProcessor::processAndStore(
                $request->file('image'),
                'products'
            );

            $image->update([
                'url' => asset("storage/{$newPath}")
            ]);

            CacheKeys::flushProducts();

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Error al reemplazar imagen: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'data' => $image->fresh()
        ]);
    }

    /**
     * Eliminar una imagen del producto.
     */
    public function destroyImage(Product $product, ProductImage $image): JsonResponse
    {
        $path = str_replace(asset('storage/') . '/', '', $image->url);

        Storage::disk('public')->delete($path);

        $image->delete();

        CacheKeys::flushProducts();

        return response()->json([
            'message' => 'Imagen eliminada.'
        ]);
    }

    public function storeVariant(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'sku'        => 'required|string|unique:product_variants,sku',
            'name'       => 'required|string',
            'price'      => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock'      => 'required|integer|min:0',
        ]);

        $variant = $product->variants()->create([
            ...$data,
            'is_active' => true
        ]);

        CacheKeys::flushProducts();

        return response()->json([
            'data' => $variant
        ], 201);
    }

    public function updateVariant(
        Request $request,
        Product $product,
        ProductVariant $variant
    ): JsonResponse {

        $data = $request->validate([
            'name'       => 'sometimes|string',
            'price'      => 'sometimes|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock'      => 'sometimes|integer|min:0',
            'is_active'  => 'sometimes|boolean',
        ]);

        $variant->update($data);

        CacheKeys::flushProducts();

        return response()->json([
            'data' => $variant
        ]);
    }

    public function destroyVariant(
        Product $product,
        ProductVariant $variant
    ): JsonResponse {

        $variant->delete();

        CacheKeys::flushProducts();

        return response()->json([
            'message' => 'Variante eliminada.'
        ]);
    }

    public function brandsByCategory(Request $request): JsonResponse
    {
        $categorySlug = $request->get('category', '');

        $categoryIds = collect();

        if ($categorySlug) {

            $categoryIds = \App\Models\Category::where('slug', $categorySlug)
                ->orWhereHas(
                    'parent',
                    fn($p) => $p->where('slug', $categorySlug)
                )
                ->pluck('id');
        }

        $brands = \App\Models\Brand::where('is_active', true)
            ->whereHas('products', function ($q) use ($categoryIds) {

                $q->where('is_active', true);

                if ($categoryIds->isNotEmpty()) {
                    $q->whereIn('category_id', $categoryIds);
                }

            })
            ->get()
            ->map(function ($brand) use ($categoryIds) {

                $count = $brand->products()
                    ->where('is_active', true)
                    ->when(
                        $categoryIds->isNotEmpty(),
                        fn($q) => $q->whereIn('category_id', $categoryIds)
                    )
                    ->count();

                $brand->product_count = $count;

                return $brand;
            })
            ->sortBy('name')
            ->values();

        return response()->json([
            'data' => $brands
        ]);
    }
}