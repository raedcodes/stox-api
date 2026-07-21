<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create roles and assign created permissions
        Role::firstOrCreate(['name' => UserRole::SuperAdmin->value]);
        Role::firstOrCreate(['name' => UserRole::Owner->value]);
        Role::firstOrCreate(['name' => UserRole::Manager->value]);
        Role::firstOrCreate(['name' => UserRole::Cashier->value]);
        Role::firstOrCreate(['name' => UserRole::StoreKeeper->value]);
    }
}
