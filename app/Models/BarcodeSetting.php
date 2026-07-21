<?php

namespace App\Models;

use App\Enums\BarcodeFormat;
use Illuminate\Database\Eloquent\Model;

class BarcodeSetting extends Model
{
    protected $fillable = [
        'format',
        'prefix',
        'next_number',
    ];

    protected function casts(): array
    {
        return [
            'format' => BarcodeFormat::class,
            'next_number' => 'integer',
        ];
    }

    /**
     * The current tenant's barcode settings. Created on demand in case a
     * tenant was provisioned before this table existed and hasn't been
     * re-migrated.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'format' => BarcodeFormat::CODE128,
            'next_number' => 1,
        ]);
    }
}
