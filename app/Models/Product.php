<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'category_id', 'brand_id', 'sku', 'name', 'slug', 'description',
        'price', 'sale_price', 'stock', 'is_active', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'price'       => 'decimal:2',
            'sale_price'  => 'decimal:2',
            'stock'       => 'integer',
            'is_active'   => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category() { return $this->belongsTo(Category::class); }
    public function brand()    { return $this->belongsTo(Brand::class); }
    public function images()   { return $this->hasMany(ProductImage::class)->orderBy('position'); }
    public function variants() { return $this->hasMany(ProductVariant::class)->where('is_active', true); }

    public function scopeActive($q)   { return $q->where('is_active', true); }
    public function scopeFeatured($q) { return $q->where('is_featured', true)->where('is_active', true); }
}