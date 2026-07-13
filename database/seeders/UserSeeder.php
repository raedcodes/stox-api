<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //create Admin
        $admin = User::create([
            'name' => 'Admin Admin',
            'email' => 'admin@business-suite.com',
            'password' => Hash::make(env('ADMIN_PASSWORD')),
            'email_verified_at' => now()
        ]);

        $admin->assignRole(UserRole::SuperAdmin);

        //create Owner
        $owner = User::create([
            'name' => 'Owner Owner',
            'email' => 'owner@business-suite.com',
            'password' => Hash::make(env('OWNER_PASSWORD')),
            'email_verified_at' => now()
        ]);
        
        $owner->assignRole(UserRole::Owner);

    }
}
