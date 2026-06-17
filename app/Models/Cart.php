<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasUlids;

    protected $fillable = ['user_id'];

    public function user()  { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(CartItem::class); }

    public function getTotalAttribute(): float
    {
        return $this->items->sum(fn($item) => $item->unit_price * $item->quantity);
    }
}