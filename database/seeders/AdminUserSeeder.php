<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'ArdhiLens Admin',
                'password' => 'Admin@123',
                'role' => 'admin',
                'is_active' => true,
                'kyc_status' => 'none',
                'email_verified_at' => now(),
                'verified_at' => now(),
            ],
        );
    }
}
