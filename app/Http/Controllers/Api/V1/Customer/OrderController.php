<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Sin acceso.'], 403);
        }

        return response()->json(['data' => $order->load('items')]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shipping_name'    => 'required|string',
            'shipping_address' => 'required|string',
            'shipping_city'    => 'required|string',
            'shipping_state'   => 'required|string',
            'shipping_zip'     => 'required|string',
            'shipping_phone'   => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        $cart = Cart::where('user_id', $request->user()->id)
            ->with(['items.product', 'items.variant'])
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'El carrito está vacío.'], 422);
        }

        $subtotal = $cart->items->sum(fn($i) => $i->unit_price * $i->quantity);

        $order = Order::create([
            ...$data,
            'user_id'          => $request->user()->id,
            'order_number'     => 'HYP-' . now()->year . '-' . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'status'           => 'pending',
            'subtotal'         => $subtotal,
            'tax'              => 0,
            'shipping'         => 0,
            'total'            => $subtotal,
            'shipping_country' => 'PE',
        ]);

        foreach ($cart->items as $item) {
            $order->items()->create([
                'product_id'   => $item->product_id,
                'variant_id'   => $item->variant_id,
                'product_name' => $item->product->name,
                'variant_name' => $item->variant?->name,
                'sku'          => $item->variant?->sku ?? $item->product->sku,
                'quantity'     => $item->quantity,
                'unit_price'   => $item->unit_price,
                'total'        => $item->unit_price * $item->quantity,
            ]);
        }

        $cart->items()->delete();

        return response()->json([
            'data'    => $order->load('items'),
            'message' => 'Pedido creado correctamente.',
        ], 201);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Sin acceso.'], 403);
        }

        if (!in_array($order->status, ['pending'])) {
            return response()->json(['message' => 'Este pedido no puede cancelarse.'], 422);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Pedido cancelado.']);
    }
}