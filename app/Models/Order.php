<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'user_id', 'order_number', 'status', 'subtotal', 'tax', 'shipping', 'total',
        'shipping_name', 'shipping_address', 'shipping_city', 'shipping_state',
        'shipping_zip', 'shipping_country', 'shipping_phone',
        'mp_preference_id', 'mp_payment_id', 'mp_payment_status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax'      => 'decimal:2',
            'shipping' => 'decimal:2',
            'total'    => 'decimal:2',
        ];
    }

    public function user()  { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
}