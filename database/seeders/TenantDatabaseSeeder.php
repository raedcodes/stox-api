<?php

namespace Database\Seeders;

use App\BarcodeFormat;
use App\Models\BarcodeSetting;
use App\Models\TaxSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a newly provisioned tenant database with its default data.
     */
    public function run(): void
    {
        TaxSetting::firstOrCreate([], ['rate' => 0]);

        BarcodeSetting::firstOrCreate([], [
            'format' => BarcodeFormat::CODE128,
            'next_number' => 1,
        ]);
    }
}
