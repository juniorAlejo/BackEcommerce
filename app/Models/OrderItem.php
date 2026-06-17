<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'order_id', 'product_id', 'variant_id', 'product_name', 'variant_name',
        'sku', 'quantity', 'unit_price', 'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'integer',
            'unit_price' => 'decimal:2',
            'total'      => 'decimal:2',
        ];
    }

    public function order() { return $this->belongsTo(Order::class); }
}