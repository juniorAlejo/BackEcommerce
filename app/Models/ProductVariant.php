<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'product_id', 'sku', 'name', 'price', 'sale_price', 'stock', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price'      => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock'      => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    public function product() { return $this->belongsTo(Product::class); }
}