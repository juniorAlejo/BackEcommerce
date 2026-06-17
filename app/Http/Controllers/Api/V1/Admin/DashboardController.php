<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_users'    => User::where('role', 'customer')->count(),
                'total_products' => Product::where('is_active', true)->count(),
                'total_orders'   => Order::count(),
                'revenue'        => Order::whereIn('status', ['paid', 'delivered'])->sum('total'),
                'recent_orders'  => Order::with('user')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(),
                'low_stock'      => Product::where('stock', '<=', 5)->where('is_active', true)->count(),
            ],
        ]);
    }
}