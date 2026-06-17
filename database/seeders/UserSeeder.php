<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'              => 'Admin HypexTech',
            'email'             => 'admin@hypextech.com',
            'password'          => Hash::make('password123'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name'              => 'Cliente Demo',
            'email'             => 'cliente@hypextech.com',
            'password'          => Hash::make('password123'),
            'role'              => 'customer',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }
}