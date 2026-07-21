<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSetting extends Model
{
    protected $fillable = [
        'rate',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
        ];
    }

    /**
     * The current tenant's tax settings. Created on demand in case a tenant
     * was provisioned before this table existed and hasn't been re-migrated.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['rate' => 0]);
    }
}
