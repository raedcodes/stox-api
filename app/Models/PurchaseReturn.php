<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Enums\SupplierTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'return_date',
        'reason',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'processed_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function total(): float
    {
        return (float) $this->items()
            ->selectRaw('SUM(quantity * unit_cost) as total')
            ->value('total');
    }

    /**
     * Products on this return that don't have enough stock at the
     * originating PO's location to cover what's being returned to the
     * supplier. Empty means it can be processed.
     *
     * @return array<string>
     */
    public function insufficientStockProducts(): array
    {
        $short = [];

        foreach ($this->items as $item) {
            $available = $item->productVariant->stockOnHand($this->purchaseOrder->location_id);

            if ((float) $item->quantity > $available) {
                $short[] = "{$item->product->name} (need {$item->quantity}, have {$available})";
            }
        }

        return $short;
    }

    /**
     * Returning goods to a supplier physically removes them from the
     * warehouse, so this deducts stock (FEFO) the same way a sale does.
     */
    public function process(): void
    {
        if ($this->processed_at !== null) {
            throw new RuntimeException('This return has already been processed.');
        }

        $short = $this->insufficientStockProducts();

        if ($short !== []) {
            throw new RuntimeException('Insufficient stock: '.implode(', ', $short));
        }

        DB::transaction(function () {
            foreach ($this->items as $item) {
                $consumed = ProductBatch::deductFefo(
                    $item->product_variant_id,
                    $this->purchaseOrder->location_id,
                    (float) $item->quantity,
                );

                foreach ($consumed as $entry) {
                    StockMovement::record(
                        batch: $entry['batch'],
                        type: StockMovementType::PurchaseReturn,
                        quantityChange: -$entry['quantity'],
                        reference: $this,
                    );
                }
            }

            $this->purchaseOrder->supplier->transactions()->create([
                'type' => SupplierTransactionType::Return,
                'amount' => $this->total(),
                'description' => "Return against {$this->purchaseOrder->po_number}",
                'transaction_date' => $this->return_date,
                'reference_type' => $this->getMorphClass(),
                'reference_id' => $this->id,
            ]);

            $this->update(['processed_at' => now()]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($this)
                ->withProperties(['po_number' => $this->purchaseOrder->po_number])
                ->log("Purchase return processed against {$this->purchaseOrder->po_number}");
        });
    }
}
