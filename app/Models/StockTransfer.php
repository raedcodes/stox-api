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
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
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
            $available = $item->product->stockOnHand($this->from_location_id);

            if ((float) $item->quantity > $available) {
                $short[] = "{$item->product->name} (need {$item->quantity}, have {$available})";
            }
        }

        return $short;
    }

    /**
     * Move stock from the source to the destination location: FEFO-deduct
     * from the source's batches, and for each batch actually consumed,
     * carry its batch number and expiry date over to a matching batch at
     * the destination (creating it if one doesn't already exist there) —
     * a transfer shouldn't lose expiry lineage just because it crossed
     * locations.
     */
    public function complete(): void
    {
        $short = $this->insufficientStockProducts();

        if ($short !== []) {
            throw new RuntimeException('Insufficient stock: '.implode(', ', $short));
        }

        DB::transaction(function () {
            foreach ($this->items as $item) {
                $consumed = ProductBatch::deductFefo(
                    $item->product_id,
                    $this->from_location_id,
                    (float) $item->quantity,
                );

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

                    $destinationBatch = ProductBatch::firstOrNew([
                        'product_id' => $item->product_id,
                        'location_id' => $this->to_location_id,
                        'batch_number' => $sourceBatch->batch_number,
                        'expiry_date' => $sourceBatch->expiry_date,
                    ]);
                    $destinationBatch->quantity = ($destinationBatch->exists ? $destinationBatch->quantity : 0) + $quantity;
                    $destinationBatch->is_active = true;
                    $destinationBatch->save();

                    StockMovement::record(
                        batch: $destinationBatch,
                        type: StockMovementType::TransferIn,
                        quantityChange: $quantity,
                        reference: $this,
                    );
                }
            }

            $this->update(['status' => StockTransferStatus::Completed]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($this)
                ->withProperties([
                    'transfer_number' => $this->transfer_number,
                    'from_location' => $this->fromLocation->name,
                    'to_location' => $this->toLocation->name,
                ])
                ->log("Stock transfer {$this->transfer_number} completed");
        });
    }
}
