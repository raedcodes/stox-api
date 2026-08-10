<?php

namespace App\Models;

use App\Enums\CustomerTransactionType;
use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'customer_type',
        'credit_limit',
        'is_walk_in',
        'email',
        'phone',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'credit_limit' => 'decimal:2',
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
     * Current balance owed by this customer: total credit sales minus total
     * payments and returns (both reduce what's owed, whether or not cash
     * actually changed hands).
     */
    public function balance(): float
    {
        $totals = $this->transactions()
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $reducing = array_sum(array_map(
            fn (CustomerTransactionType $type) => (float) ($totals[$type->value] ?? 0),
            CustomerTransactionType::reducingTypes(),
        ));

        return (float) ($totals[CustomerTransactionType::CreditSale->value] ?? 0) - $reducing;
    }
}
