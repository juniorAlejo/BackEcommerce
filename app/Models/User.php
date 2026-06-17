<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUlids, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'google_id', 'avatar', 'role', 'is_active', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function isAdmin(): bool     { return $this->role === 'admin'; }
    public function hasPassword(): bool { return !is_null($this->password); }
    public function cart()              { return $this->hasOne(Cart::class); }
    public function orders()            { return $this->hasMany(Order::class); }
    public function addresses()
{
    return $this->hasMany(Address::class);
}
}