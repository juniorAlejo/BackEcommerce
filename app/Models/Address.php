<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id', 'label', 'recipient_name', 'phone',
        'address_line', 'district', 'city', 'reference', 'is_default',
        'province', 'zip_code', 'customs_id', 'customs_first_name', 'customs_last_name',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}