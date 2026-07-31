<?php

namespace Database\Seeders;

use App\Enums\BusinessType;
use App\Enums\Currency;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoBusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['name' => 'Demo Business'],
            [
                'business_type' => BusinessType::Retail,
                'currency' => Currency::USD,
                'timezone' => config('app.timezone'),
            ],
        );

        if ($tenant->domain()->doesntExist()) {
            $tenant->domains()->create(['domain' => env('DEMO_TENANT_DOMAIN', 'demo')]);
        }

        $owner = User::firstOrCreate(
            ['email' => 'owner@business-suite.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Owner Owner',
                'password' => Hash::make(env('OWNER_PASSWORD')),
                'email_verified_at' => now(),
            ]
        );

        $owner->assignRole(UserRole::Owner);
    }
}
