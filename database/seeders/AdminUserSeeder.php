<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@hrono.local')],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => Hash::make((string) env('ADMIN_PASSWORD', 'password')),
            ],
        );
    }
}
