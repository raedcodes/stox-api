<?php

namespace Database\Seeders;

use App\BarcodeFormat;
use App\LocationType;
use App\Models\BarcodeSetting;
use App\Models\Location;
use App\Models\TaxSetting;
use App\Models\UnitOfMeasure;
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

        foreach ([
            ['name' => 'Piece', 'abbreviation' => 'pc'],
            ['name' => 'Kilogram', 'abbreviation' => 'kg'],
            ['name' => 'Liter', 'abbreviation' => 'L'],
            ['name' => 'Box', 'abbreviation' => 'box'],
            ['name' => 'Pack', 'abbreviation' => 'pack'],
        ] as $unit) {
            UnitOfMeasure::firstOrCreate(['abbreviation' => $unit['abbreviation']], $unit);
        }

        Location::firstOrCreate(['name' => 'Main Location'], [
            'type' => LocationType::Store,
            'is_active' => true,
        ]);
    }
}
