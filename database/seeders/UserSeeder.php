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
        $admin = User::firstorCreate(
            ['email' => 'admin@business-suite.com'],
            [
                'name' => 'Admin Admin', 
                'password' => Hash::make(env('ADMIN_PASSWORD')), 
                'email_verified_at' => now()
            ],
        );

        $admin->assignRole(UserRole::SuperAdmin);

        //create Owner

        $owner = User::firstorCreate(
            ['email' => 'owner@business-suite.com'],
            [
                'name' => 'Owner Owner',
                'password' => Hash::make(env('OWNER_PASSWORD')),
                'email_verified_at' => now()
            ]
        );
        
        $owner->assignRole(UserRole::Owner);

    }
}
