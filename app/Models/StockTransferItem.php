<?php

namespace App\Models;

use App\Models\Concerns\HasDefaultProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    use HasDefaultProductVariant;

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'quantity_shipped',
        'quantity_received',
        'shipped_unit_cost',
        'shipped_expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'quantity_shipped' => 'decimal:2',
            'quantity_received' => 'decimal:2',
            'shipped_unit_cost' => 'decimal:2',
            'shipped_expiry_date' => 'date',
        ];
    }

    /**
     * Still outstanding on this item — shipped but not yet confirmed
     * arrived at the destination.
     */
    public function remainingToReceive(): float
    {
        return (float) $this->quantity_shipped - (float) $this->quantity_received;
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
