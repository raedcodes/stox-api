<?php

namespace Database\Seeders;

use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create roles and assign created permissions
        Role::create(['name' => UserRole::SuperAdmin]);
        Role::create(['name' => UserRole::Owner]);
        Role::create(['name' => UserRole::Manager]);
        Role::create(['name' => UserRole::Cashier]);
        Role::create(['name' => UserRole::StoreKeeper]);
    }
}
