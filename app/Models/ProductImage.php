<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasUlids;

    protected $fillable = ['product_id', 'url', 'alt', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function product() { return $this->belongsTo(Product::class); }
}