<?php

namespace App\Models;

use App\Enums\PaymentTerms;
use App\Enums\SupplierTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
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
     * Current balance owed to this supplier: total charges minus total payments.
     */
    public function balance(): float
    {
        $totals = $this->transactions()
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return (float) ($totals[SupplierTransactionType::Charge->value] ?? 0)
            - (float) ($totals[SupplierTransactionType::Payment->value] ?? 0);
    }
}
