<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@business-suite.com'],
            [
                'name' => 'Admin Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'email_verified_at' => now(),
            ],
        );

        $admin->assignRole(UserRole::SuperAdmin);
    }
}
