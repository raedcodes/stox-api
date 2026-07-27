<?php

namespace App\Models;

use App\Enums\CustomerTransactionType;
use App\Enums\PaymentMethod;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleReturn extends Model
{
    protected $fillable = [
        'sale_id',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function total(): float
    {
        return (float) $this->items()
            ->selectRaw('SUM(quantity * unit_price) as total')
            ->value('total');
    }

    /**
     * Restock returned quantities as a new batch per product (we don't try to
     * reverse the exact FEFO batches the original sale deducted from), and
     * record a refund against the customer's ledger if they'd been charged
     * on credit.
     */
    public function process(): void
    {
        if ($this->processed_at !== null) {
            throw new RuntimeException('This return has already been processed.');
        }

        DB::transaction(function () {
            foreach ($this->items as $item) {
                $batch = ProductBatch::create([
                    'product_id' => $item->product_id,
                    'location_id' => $this->sale->location_id,
                    'batch_number' => null,
                    'quantity' => $item->quantity,
                ]);

                StockMovement::record(
                    batch: $batch,
                    type: StockMovementType::SaleReturn,
                    quantityChange: (float) $item->quantity,
                    reference: $this,
                );
            }

            if ($this->sale->payment_method === PaymentMethod::Credit) {
                $this->sale->customer->transactions()->create([
                    'type' => CustomerTransactionType::Payment,
                    'amount' => $this->total(),
                    'description' => "Return against {$this->sale->sale_number}",
                    'transaction_date' => $this->return_date,
                    'reference_type' => $this->getMorphClass(),
                    'reference_id' => $this->id,
                ]);
            }

            $this->update(['processed_at' => now()]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($this)
                ->withProperties(['sale_number' => $this->sale->sale_number])
                ->log("Sale return processed against {$this->sale->sale_number}");
        });
    }
}
