<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'is_walk_in',
        'email',
        'phone',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_walk_in' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The tenant's default customer for anonymous/quick sales. Created on
     * demand in case a tenant was provisioned before this concept existed.
     */
    public static function walkIn(): self
    {
        return static::query()->firstOrCreate(
            ['is_walk_in' => true],
            ['name' => 'Walk-in Customer']
        );
    }

    protected static function booted(): void
    {
        static::deleting(function (self $customer) {
            if ($customer->is_walk_in) {
                return false;
            }
        });
    }
}
