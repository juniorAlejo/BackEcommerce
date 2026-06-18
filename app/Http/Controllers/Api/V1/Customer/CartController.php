<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function getOrCreateCart(Request $request): Cart
    {
        return Cart::firstOrCreate(['user_id' => $request->user()->id]);
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $cart->load(['items.product.images' => fn($q) => $q->where('position', 0), 'items.variant']);

        return response()->json([
            'data' => [
                'id'    => $cart->id,
                'items' => $cart->items,
                'total' => $cart->total,
            ],
        ]);
    }

    public function addItem(Request $request): JsonResponse
{
    $data = $request->validate([
        'product_id' => 'required|exists:products,id',
        'variant_id' => 'nullable|exists:product_variants,id',
        'quantity'   => 'required|integer|min:1|max:99',
    ]);

    $data['variant_id'] = $data['variant_id'] ?? null;

    $product = \App\Models\Product::findOrFail($data['product_id']);

    // Calcular precio
    if ($data['variant_id']) {
        $variant = \App\Models\ProductVariant::find($data['variant_id']);
        $price   = $variant->sale_price ?? $variant->price;
    } else {
        $price = $product->sale_price ?? $product->price;
    }

    $cart = $this->getOrCreateCart($request);

    CartItem::updateOrCreate(
        [
            'cart_id'    => $cart->id,
            'product_id' => $data['product_id'],
            'variant_id' => $data['variant_id'],
        ],
        [
            'quantity'   => $data['quantity'],
            'unit_price' => $price,
        ]
    );

    $cart->load(['items.product.images' => fn($q) => $q->where('position', 0), 'items.product.brand', 'items.variant']);

    return response()->json([
        'data' => [
            'id'    => $cart->id,
            'items' => $cart->items,
            'total' => $cart->total,
        ],
        'message' => 'Producto agregado al carrito.',
    ]);
}

    public function updateItem(Request $request, CartItem $item): JsonResponse
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:99']);
        $item->update($data);
        $cart = $item->cart->load(['items.product.images' => fn($q) => $q->where('position', 0), 'items.variant']);

        return response()->json([
            'data' => ['id' => $cart->id, 'items' => $cart->items, 'total' => $cart->total],
        ]);
    }

    public function removeItem(CartItem $item): JsonResponse
    {
        $cart = $item->cart;
        $item->delete();
        $cart->load(['items.product.images' => fn($q) => $q->where('position', 0), 'items.variant']);

        return response()->json([
            'data'    => ['id' => $cart->id, 'items' => $cart->items, 'total' => $cart->total],
            'message' => 'Producto eliminado del carrito.',
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();

        return response()->json(['message' => 'Carrito vaciado.']);
    }
}