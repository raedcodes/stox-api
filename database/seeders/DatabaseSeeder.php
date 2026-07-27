<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed only what any environment needs to be usable: roles and the
     * SuperAdmin. Deliberately excludes DemoBusinessSeeder — that's local-dev
     * only and is run separately, on purpose, so this stays production-safe.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SuperAdminSeeder::class,
            DemoBusinessSeeder::class,
        ]);
    }
}
