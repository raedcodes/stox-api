<?php

namespace Database\Seeders;

use App\BusinessType;
use App\Currency;
use App\Models\Tenant;
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
        $admin = User::firstOrCreate(
            ['email' => 'admin@business-suite.com'],
            [
                'name' => 'Admin Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'email_verified_at' => now()
            ],
        );

        $admin->assignRole(UserRole::SuperAdmin);

        //create Owner, with a business of their own — every Owner belongs to a tenant

        $tenant = Tenant::firstOrCreate(
            ['name' => 'Demo Business'],
            [
                'business_type' => BusinessType::Retail,
                'currency' => Currency::USD,
                'timezone' => config('app.timezone'),
            ],
        );

        if ($tenant->domain()->doesntExist()) {
            $tenant->domains()->create(['domain' => 'demo']);
        }

        $owner = User::firstOrCreate(
            ['email' => 'owner@business-suite.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Owner Owner',
                'password' => Hash::make(env('OWNER_PASSWORD')),
                'email_verified_at' => now()
            ]
        );

        $owner->assignRole(UserRole::Owner);

    }
}
