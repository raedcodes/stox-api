<?php

namespace App\Models;

use App\Enums\CustomerTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerTransaction extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'amount',
        'description',
        'transaction_date',
    ];

    protected function casts(): array
    {
        return [
            'type' => CustomerTransactionType::class,
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
