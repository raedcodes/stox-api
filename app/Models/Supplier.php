<?php

namespace App\Models;

use App\Enums\PaymentTerms;
use App\Enums\SupplierTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'payment_terms',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'payment_terms' => PaymentTerms::class,
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SupplierTransaction::class);
    }

    /**
     * Current balance owed to this supplier: total charges minus total
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
            fn (SupplierTransactionType $type) => (float) ($totals[$type->value] ?? 0),
            SupplierTransactionType::reducingTypes(),
        ));

        return (float) ($totals[SupplierTransactionType::Charge->value] ?? 0) - $reducing;
    }
}
