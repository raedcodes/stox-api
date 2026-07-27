<?php

namespace App\Models;

use App\Enums\CustomerTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerTransaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'customer_id',
        'type',
        'amount',
        'description',
        'transaction_date',
        'reference_type',
        'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => CustomerTransactionType::class,
            'amount' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $transaction) {
            if (blank($transaction->transaction_number)) {
                $transaction->transaction_number = static::generateTransactionNumber();
            }
        });
    }

    protected static function generateTransactionNumber(): string
    {
        $next = (int) (static::query()->max('id')) + 1;

        return 'TXN-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * A human-readable label for whatever this transaction is tied back to,
     * since the reference is polymorphic and each source names itself
     * differently. Manually-entered transactions have no reference.
     */
    public function referenceLabel(): ?string
    {
        return match (true) {
            $this->reference instanceof Sale => "Sale {$this->reference->sale_number}",
            $this->reference instanceof SaleReturn => "Sale return #{$this->reference->id} (Sale {$this->reference->sale?->sale_number})",
            default => null,
        };
    }
}
