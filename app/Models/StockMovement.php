<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'location_id',
        'product_batch_id',
        'type',
        'quantity_change',
        'quantity_after',
        'reason',
        'notes',
        'reference_type',
        'reference_id',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity_change' => 'decimal:2',
            'quantity_after' => 'decimal:2',
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

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * A human-readable label for whatever this movement is tied back to,
     * since the reference is polymorphic and each source names itself
     * differently.
     */
    public function referenceLabel(): ?string
    {
        return match (true) {
            $this->reference instanceof Sale => "Sale {$this->reference->sale_number}",
            $this->reference instanceof SaleReturn => "Sale return #{$this->reference->id} (Sale {$this->reference->sale?->sale_number})",
            $this->reference instanceof PurchaseOrder => "Purchase order {$this->reference->po_number}",
            $this->reference instanceof PurchaseReturn => "Purchase return #{$this->reference->id} (PO {$this->reference->purchaseOrder?->po_number})",
            $this->reference instanceof StockTransfer => "Transfer {$this->reference->transfer_number}",
            $this->reference instanceof StockAdjustment => "Adjustment #{$this->reference->id}",
            default => null,
        };
    }

    /**
     * Record one movement against a batch, snapshotting the batch's quantity
     * after the change so the ledger is a point-in-time audit trail even if
     * the batch is altered again later.
     */
    public static function record(
        ProductBatch $batch,
        StockMovementType $type,
        float $quantityChange,
        ?string $reason = null,
        ?string $notes = null,
        ?Model $reference = null,
    ): self {
        return static::create([
            'product_id' => $batch->product_id,
            'location_id' => $batch->location_id,
            'product_batch_id' => $batch->id,
            'type' => $type,
            'quantity_change' => $quantityChange,
            'quantity_after' => $batch->fresh()->quantity,
            'reason' => $reason,
            'notes' => $notes,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'performed_by' => auth()->id(),
        ]);
    }
}
