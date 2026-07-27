<?php

namespace App\Models;

use App\Enums\SupplierTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupplierTransaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'supplier_id',
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
            'type' => SupplierTransactionType::class,
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
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
            $this->reference instanceof PurchaseOrder => "Purchase order {$this->reference->po_number}",
            default => null,
        };
    }
}
