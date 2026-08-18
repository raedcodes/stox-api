<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockTransfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'from_location_id',
        'to_location_id',
        'status',
        'transfer_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => StockTransferStatus::class,
            'transfer_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $transfer) {
            if (blank($transfer->transfer_number)) {
                $transfer->transfer_number = static::generateTransferNumber();
            }
        });
    }

    protected static function generateTransferNumber(): string
    {
        $next = (int) (static::query()->max('id')) + 1;

        return 'TRF-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id')->withTrashed();
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    /**
     * Products on this transfer that don't have enough stock at the source
     * location to cover what's requested. Empty means it can be completed.
     *
     * @return array<string>
     */
    public function insufficientStockProducts(): array
    {
        $short = [];

        foreach ($this->items as $item) {
            $available = $item->productVariant->stockOnHand($this->from_location_id);

            if ((float) $item->quantity > $available) {
                $short[] = "{$item->product->name} (need {$item->quantity}, have {$available})";
            }
        }

        return $short;
    }

    public function submitForApproval(): void
    {
        if ($this->status !== StockTransferStatus::Draft) {
            throw new RuntimeException('Only a draft transfer can be submitted for approval.');
        }

        if ($this->items()->doesntExist()) {
            throw new RuntimeException('Add at least one item before submitting for approval.');
        }

        $this->update(['status' => StockTransferStatus::PendingApproval]);

        activity()->causedBy(auth()->user())->performedOn($this)
            ->log("Stock transfer {$this->transfer_number} submitted for approval");
    }

    public function approve(): void
    {
        if ($this->status !== StockTransferStatus::PendingApproval) {
            throw new RuntimeException('Only a transfer pending approval can be approved.');
        }

        $this->update(['status' => StockTransferStatus::Approved]);

        activity()->causedBy(auth()->user())->performedOn($this)
            ->log("Stock transfer {$this->transfer_number} approved");
    }

    public function reject(?string $reason = null): void
    {
        if ($this->status !== StockTransferStatus::PendingApproval) {
            throw new RuntimeException('Only a transfer pending approval can be rejected.');
        }

        $this->update([
            'status' => StockTransferStatus::Rejected,
            'notes' => trim(($this->notes ? $this->notes."\n" : '').'Rejected: '.($reason ?: 'no reason given')),
        ]);

        activity()->causedBy(auth()->user())->performedOn($this)
            ->log("Stock transfer {$this->transfer_number} rejected");
    }

    public function cancel(): void
    {
        if (! in_array($this->status, [StockTransferStatus::Draft, StockTransferStatus::PendingApproval, StockTransferStatus::Approved], true)) {
            throw new RuntimeException('A transfer already in transit or completed cannot be cancelled — stock has already moved.');
        }

        $this->update(['status' => StockTransferStatus::Cancelled]);

        activity()->causedBy(auth()->user())->performedOn($this)
            ->log("Stock transfer {$this->transfer_number} cancelled");
    }

    /**
     * Ships the approved transfer: FEFO-deducts each item's full requested
     * quantity from the source location right now — the goods have
     * physically left — recording a TransferOut movement per source batch
     * consumed. Nothing is credited to the destination yet; that only
     * happens as receive() confirms what actually arrived, which can be
     * less than what shipped. Each item's shipped cost/expiry is captured
     * as a weighted average / earliest-expiry across whichever source
     * batches FEFO consumed for it, so receive() can recreate the right
     * cost basis at the destination without tracking every source batch
     * individually through the transit leg.
     */
    public function markInTransit(): void
    {
        if ($this->status !== StockTransferStatus::Approved) {
            throw new RuntimeException('Only an approved transfer can be marked in transit.');
        }

        $short = $this->insufficientStockProducts();

        if ($short !== []) {
            throw new RuntimeException('Insufficient stock: '.implode(', ', $short));
        }

        DB::transaction(function () {
            foreach ($this->items as $item) {
                $consumed = ProductBatch::deductFefo(
                    $item->product_variant_id,
                    $this->from_location_id,
                    (float) $item->quantity,
                );

                $shippedQuantity = 0;
                $costWeightedSum = 0;
                $earliestExpiry = null;

                foreach ($consumed as $entry) {
                    /** @var ProductBatch $sourceBatch */
                    $sourceBatch = $entry['batch'];
                    $quantity = $entry['quantity'];

                    StockMovement::record(
                        batch: $sourceBatch,
                        type: StockMovementType::TransferOut,
                        quantityChange: -$quantity,
                        reference: $this,
                    );

                    $shippedQuantity += $quantity;
                    $costWeightedSum += $quantity * (float) $sourceBatch->unit_cost;

                    if ($sourceBatch->expiry_date && (! $earliestExpiry || $sourceBatch->expiry_date->lt($earliestExpiry))) {
                        $earliestExpiry = $sourceBatch->expiry_date;
                    }
                }

                $item->update([
                    'quantity_shipped' => $shippedQuantity,
                    'shipped_unit_cost' => $shippedQuantity > 0 ? $costWeightedSum / $shippedQuantity : 0,
                    'shipped_expiry_date' => $earliestExpiry,
                ]);
            }

            $this->update(['status' => StockTransferStatus::InTransit]);

            activity()->causedBy(auth()->user())->performedOn($this)
                ->withProperties(['transfer_number' => $this->transfer_number])
                ->log("Stock transfer {$this->transfer_number} marked in transit");
        });
    }

    /**
     * Credits the destination location for whatever quantity actually
     * arrived per item — can be less than shipped (loss/damage in
     * transit). Moves to Completed once every item's received quantity
     * matches what shipped, PartiallyReceived otherwise, so a short
     * delivery doesn't silently look "done."
     *
     * @param  array<int, float>  $receivedQuantities  keyed by stock_transfer_item id
     */
    public function receive(array $receivedQuantities): void
    {
        if (! in_array($this->status, [StockTransferStatus::InTransit, StockTransferStatus::PartiallyReceived], true)) {
            throw new RuntimeException('Only a transfer that is in transit can be received.');
        }

        DB::transaction(function () use ($receivedQuantities) {
            foreach ($this->items as $item) {
                $quantity = (float) ($receivedQuantities[$item->id] ?? 0);

                if ($quantity <= 0) {
                    continue;
                }

                $remaining = $item->remainingToReceive();

                if ($quantity > $remaining) {
                    throw new RuntimeException("Cannot receive more than the {$remaining} still outstanding for {$item->product->name}.");
                }

                $destinationBatch = ProductBatch::firstOrNew([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'location_id' => $this->to_location_id,
                    'batch_number' => null,
                    'expiry_date' => $item->shipped_expiry_date,
                ]);

                $existingQuantity = $destinationBatch->exists ? (float) $destinationBatch->quantity : 0;
                $existingUnitCost = $destinationBatch->exists ? (float) $destinationBatch->unit_cost : 0;
                $shippedUnitCost = (float) $item->shipped_unit_cost;

                $destinationBatch->unit_cost = ($existingQuantity + $quantity) > 0
                    ? (($existingQuantity * $existingUnitCost) + ($quantity * $shippedUnitCost)) / ($existingQuantity + $quantity)
                    : $shippedUnitCost;

                $destinationBatch->quantity = $existingQuantity + $quantity;
                $destinationBatch->is_active = true;
                $destinationBatch->save();

                StockMovement::record(
                    batch: $destinationBatch,
                    type: StockMovementType::TransferIn,
                    quantityChange: $quantity,
                    reference: $this,
                );

                $item->increment('quantity_received', $quantity);
            }

            $this->recomputeStatus();
        });
    }

    /**
     * Completed once every item's received quantity matches what shipped,
     * PartiallyReceived otherwise, so a short delivery doesn't silently
     * look "done." Mirrors PurchaseOrder::recomputeStatus()'s shape.
     */
    protected function recomputeStatus(): void
    {
        $allReceived = $this->items()->get()->every(
            fn (StockTransferItem $item) => (float) $item->quantity_received >= (float) $item->quantity_shipped
        );

        $this->update(['status' => $allReceived ? StockTransferStatus::Completed : StockTransferStatus::PartiallyReceived]);

        activity()->causedBy(auth()->user())->performedOn($this)
            ->withProperties(['transfer_number' => $this->transfer_number])
            ->log($allReceived
                ? "Stock transfer {$this->transfer_number} completed"
                : "Stock transfer {$this->transfer_number} partially received");
    }
}
