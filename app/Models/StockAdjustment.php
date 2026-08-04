<?php

namespace App\Models;

use App\Enums\StockAdjustmentReason;
use App\Enums\StockMovementType;
use App\Models\Concerns\HasDefaultProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class StockAdjustment extends Model
{
    use HasDefaultProductVariant;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'location_id',
        'product_batch_id',
        'quantity_change',
        'reason',
        'notes',
        'adjustment_date',
    ];

    protected function casts(): array
    {
        return [
            'reason' => StockAdjustmentReason::class,
            'quantity_change' => 'decimal:2',
            'adjustment_date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    /**
     * Apply the adjustment to its batch and log it. Called explicitly by the
     * creating page (wrapped in the same transaction as the record's own
     * creation) rather than from a model event — a failed adjustment must
     * never leave behind a StockAdjustment row with no matching batch change.
     * A positive change tops up the batch directly; a negative change can't
     * take a batch below zero (that's what a full FEFO deduction across
     * batches is for — an adjustment targets one specific, already-identified
     * batch).
     */
    public function apply(): void
    {
        // Locked for the duration of the caller's transaction, so a
        // concurrent adjustment or sale against the same batch can't read a
        // stale quantity and push it negative.
        $batch = $this->batch()->lockForUpdate()->firstOrFail();

        if ($this->quantity_change < 0 && (float) $batch->quantity < abs((float) $this->quantity_change)) {
            throw new RuntimeException(
                "Cannot adjust {$batch->product->name} down by ".abs($this->quantity_change).
                " — batch only has {$batch->quantity} on hand."
            );
        }

        $batch->increment('quantity', $this->quantity_change);

        StockMovement::record(
            batch: $batch,
            type: StockMovementType::Adjustment,
            quantityChange: (float) $this->quantity_change,
            reason: $this->reason->value,
            notes: $this->notes,
            reference: $this,
        );

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this)
            ->withProperties([
                'product' => $batch->product->name,
                'reason' => $this->reason->value,
                'quantity_change' => (float) $this->quantity_change,
            ])
            ->log("Stock adjustment ({$this->reason->getLabel()}) for {$batch->product->name}: {$this->quantity_change}");
    }
}
