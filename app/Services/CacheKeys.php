<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheKeys
{
    public const PRODUCTS_PREFIX  = 'products:';
    public const CATEGORIES       = 'categories:all';
    public const BRANDS           = 'brands:all';
    public const BRANDS_BY_CAT    = 'brands:by-category:';
    public const NAVBAR           = 'navbar:index';
    public const BANNERS          = 'banners:active';

    /**
     * Limpia todo lo relacionado a productos y catálogo.
     * Se llama cada vez que se crea/edita/borra un producto.
     */
    public static function flushProducts(): void
    {
        Cache::flush(); // en file driver no hay flush por prefijo, así que limpiamos todo
    }

    public static function flushCategories(): void
    {
        Cache::forget(self::CATEGORIES);
        Cache::forget(self::NAVBAR);
    }

    public static function flushBrands(): void
    {
        Cache::forget(self::BRANDS);
        Cache::forget(self::NAVBAR);
    }

    public static function flushBanners(): void
    {
        Cache::forget(self::BANNERS);
    }
}