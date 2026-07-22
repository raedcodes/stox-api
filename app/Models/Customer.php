<?php

namespace App\Models;

use App\Enums\CustomerTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

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

    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerTransaction::class);
    }

    /**
     * Current balance owed by this customer: total credit sales minus total payments.
     */
    public function balance(): float
    {
        $totals = $this->transactions()
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return (float) ($totals[CustomerTransactionType::CreditSale->value] ?? 0)
            - (float) ($totals[CustomerTransactionType::Payment->value] ?? 0);
    }
}
