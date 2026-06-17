<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Customer\ProductController;
use App\Http\Controllers\Api\V1\Customer\CartController;
use App\Http\Controllers\Api\V1\Customer\OrderController;
use App\Http\Controllers\Api\V1\Customer\ProfileController;
use App\Http\Controllers\Api\V1\Customer\NavbarController;
use App\Http\Controllers\Api\V1\Customer\SearchController;
use App\Http\Controllers\Api\V1\Customer\AddressController;

/*
|--------------------------------------------------------------------------
| API Routes — HypexTech v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Autenticación pública ──────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);
        Route::post('forgot-password', [PasswordResetController::class, 'sendLink']);
        Route::post('reset-password',  [PasswordResetController::class, 'reset']);

        // Google OAuth
        // Google OAuth
        Route::get('google/redirect',          [GoogleAuthController::class, 'redirect']);
        Route::get('google/callback',          [GoogleAuthController::class, 'callback']);
        Route::post('google/verify-code',      [GoogleAuthController::class, 'verifyCode']);
        Route::post('google/create-password',  [GoogleAuthController::class, 'createPassword']);
        Route::post('google/login-password',   [GoogleAuthController::class, 'loginWithPassword']);
        Route::post('verify-reset-code', [PasswordResetController::class, 'verifyCode']);
    });

    // ── Catálogo público (con caché HTTP para mayor velocidad) ──────────
Route::middleware('cache.response:60')->group(function () {
    Route::get('products',        [ProductController::class, 'index']);
    Route::get('products/{slug}', [ProductController::class, 'show']);
    Route::get('search',          [SearchController::class, 'search']);
});

Route::middleware('cache.response:120')->group(function () {
    Route::get('categories',         [ProductController::class, 'categories']);
    Route::get('brands',             [ProductController::class, 'brands']);
    Route::get('brands-by-category', [ProductController::class, 'brandsByCategory']);
    Route::get('navbar',             [NavbarController::class, 'index']);
    Route::get('banners',            [\App\Http\Controllers\Api\V1\Admin\BannerController::class, 'index']);
});

    // ── Rutas protegidas (Sanctum) ─────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',      [AuthController::class, 'me']);

        // Perfil del cliente
        Route::prefix('profile')->group(function () {
            Route::get('/',                [ProfileController::class, 'show']);
            Route::put('/',                [ProfileController::class, 'update']);
            Route::post('password',        [ProfileController::class, 'setPassword']);
            Route::post('avatar',          [ProfileController::class, 'uploadAvatar']);
        });

        // Direcciones del cliente
Route::prefix('addresses')->group(function () {
    Route::get('/',           [AddressController::class, 'index']);
    Route::post('/',          [AddressController::class, 'store']);
    Route::put('{address}',   [AddressController::class, 'update']);
    Route::delete('{address}',[AddressController::class, 'destroy']);
});

        // Carrito
        Route::prefix('cart')->group(function () {
            Route::get('/',        [CartController::class, 'index']);
            Route::post('/',       [CartController::class, 'addItem']);
            Route::put('{item}',   [CartController::class, 'updateItem']);
            Route::delete('{item}',[CartController::class, 'removeItem']);
            Route::delete('/',     [CartController::class, 'clear']);
        });

        // Pedidos del cliente
        Route::prefix('orders')->group(function () {
            Route::get('/',        [OrderController::class, 'index']);
            Route::post('/',       [OrderController::class, 'store']);
            Route::get('{order}',  [OrderController::class, 'show']);
            Route::post('{order}/cancel', [OrderController::class, 'cancel']);
        });
        

        // ── Panel Admin ────────────────────────────────────────────────
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index']);

            // Productos
            Route::apiResource('products', AdminProductController::class);

            // Imágenes de productos
            Route::post('products/{product}/images',              [AdminProductController::class, 'storeImages']);
            Route::post('products/{product}/images/{image}/replace', [AdminProductController::class, 'replaceImage']);
            Route::delete('products/{product}/images/{image}',    [AdminProductController::class, 'destroyImage']);

            // Variantes de productos
            Route::post('products/{product}/variants',            [AdminProductController::class, 'storeVariant']);
            Route::put('products/{product}/variants/{variant}',    [AdminProductController::class, 'updateVariant']);
            Route::delete('products/{product}/variants/{variant}', [AdminProductController::class, 'destroyVariant']);

            // Destacar producto
            Route::post('products/{product}/toggle-featured', [AdminProductController::class, 'toggleFeatured']);

            // Categorías
            Route::apiResource('categories', AdminCategoryController::class);

            // Marcas
            Route::apiResource('brands', AdminBrandController::class);

            // Banners
            Route::apiResource('banners', AdminBannerController::class);

            // Pedidos
            Route::get('orders',                [AdminOrderController::class, 'index']);
            Route::get('orders/{order}',        [AdminOrderController::class, 'show']);
            Route::put('orders/{order}/status', [AdminOrderController::class, 'updateStatus']);

            // Usuarios
            Route::get('users',           [AdminUserController::class, 'index']);
            Route::get('users/{user}',    [AdminUserController::class, 'show']);
            Route::put('users/{user}',    [AdminUserController::class, 'update']);
            Route::delete('users/{user}', [AdminUserController::class, 'destroy']);
        });
    });

    // ── Webhooks de Mercado Pago (sin auth) ───────────────────────────
    Route::post('webhooks/mercadopago', [\App\Http\Controllers\Api\V1\WebhookController::class, 'mercadopago']);
});
