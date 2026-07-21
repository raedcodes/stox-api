<?php

namespace App\Models;

use App\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'supplier_id',
        'location_id',
        'po_number',
        'status',
        'order_date',
        'expected_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (blank($order->po_number)) {
                $order->po_number = static::generatePoNumber();
            }
        });
    }

    protected static function generatePoNumber(): string
    {
        $next = (int) (static::query()->max('id')) + 1;

        return 'PO-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(PurchaseOrderCharge::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceipt::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /**
     * The order's face value: ordered quantities at their agreed unit cost.
     */
    public function subtotal(): float
    {
        return (float) $this->items()
            ->selectRaw('SUM(quantity_ordered * unit_cost) as total')
            ->value('total');
    }

    /**
     * Additional charges (freight, customs, handling, etc.) — the "landed cost" on top of the order value.
     */
    public function landedCostTotal(): float
    {
        return (float) $this->charges()->sum('amount');
    }

    public function total(): float
    {
        return $this->subtotal() + $this->landedCostTotal();
    }

    /**
     * Recompute status from each item's received-vs-ordered quantity. Only
     * touches Ordered/Partially Received/Received — never overrides Draft
     * or Cancelled, which are set explicitly.
     */
    public function recomputeStatus(): void
    {
        if (in_array($this->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Cancelled], true)) {
            return;
        }

        $items = $this->items()->get(['quantity_ordered', 'quantity_received']);

        $totalReceived = $items->sum('quantity_received');

        $status = match (true) {
            $totalReceived <= 0 => PurchaseOrderStatus::Ordered,
            $items->every(fn (PurchaseOrderItem $item) => $item->quantity_received >= $item->quantity_ordered) => PurchaseOrderStatus::Received,
            default => PurchaseOrderStatus::PartiallyReceived,
        };

        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }
}
