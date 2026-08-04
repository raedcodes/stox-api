<?php

namespace App\Models;

use App\Enums\CustomerTransactionType;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Sale extends Model
{
    protected $fillable = [
        'customer_id',
        'location_id',
        'sale_number',
        'status',
        'payment_method',
        'discount_type',
        'discount_value',
        'sale_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SaleStatus::class,
            'payment_method' => PaymentMethod::class,
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'sale_date' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $sale) {
            if (blank($sale->sale_number)) {
                $sale->sale_number = static::generateSaleNumber();
            }

            if (blank($sale->created_by)) {
                $sale->created_by = auth()->id();
            }
        });
    }

    protected static function generateSaleNumber(): string
    {
        $next = (int) (static::query()->max('id')) + 1;

        return 'INV-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subtotal(): float
    {
        return $this->items->sum(fn (SaleItem $item) => $item->subtotal());
    }

    public function taxTotal(): float
    {
        return $this->items->sum(fn (SaleItem $item) => $item->taxAmount());
    }

    /**
     * Invoice-level discount, applied after tax — a straight deduction off
     * the tax-included running total (e.g. a loyalty discount), rather than
     * feeding back into the taxable base.
     */
    public function invoiceDiscountAmount(): float
    {
        return DiscountType::amount(
            $this->discount_type,
            $this->discount_value !== null ? (float) $this->discount_value : null,
            $this->subtotal() + $this->taxTotal(),
        );
    }

    public function total(): float
    {
        return $this->subtotal() + $this->taxTotal() - $this->invoiceDiscountAmount();
    }

    /**
     * Products that don't have enough stock at this sale's location to cover
     * what's on the invoice. Empty means the sale can be completed.
     *
     * @return array<string>
     */
    public function insufficientStockProducts(): array
    {
        $short = [];

        foreach ($this->items as $item) {
            $available = $item->productVariant->stockOnHand($this->location_id);

            if ((float) $item->quantity > $available) {
                $short[] = "{$item->product->name} (need {$item->quantity}, have {$available})";
            }
        }

        return $short;
    }

    /**
     * Finalize the sale: deduct stock (FEFO), record a customer ledger charge
     * if sold on credit, and mark as completed.
     */
    public function complete(): void
    {
        $short = $this->insufficientStockProducts();

        if ($short !== []) {
            throw new RuntimeException('Insufficient stock: '.implode(', ', $short));
        }

        DB::transaction(function () {
            $taxRate = (float) TaxSetting::current()->rate;

            foreach ($this->items as $item) {
                $item->update(['tax_rate' => $taxRate]);
                $this->deductStock($item);
            }

            if ($this->payment_method === PaymentMethod::Credit) {
                $this->customer->transactions()->create([
                    'type' => CustomerTransactionType::CreditSale,
                    'amount' => $this->total(),
                    'description' => "Sale {$this->sale_number}",
                    'transaction_date' => $this->sale_date,
                    'reference_type' => $this->getMorphClass(),
                    'reference_id' => $this->id,
                ]);
            }

            $this->update(['status' => SaleStatus::Completed]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($this)
                ->withProperties(['sale_number' => $this->sale_number, 'total' => $this->total()])
                ->log("Sale {$this->sale_number} completed");
        });
    }

    protected function deductStock(SaleItem $item): void
    {
        $consumed = ProductBatch::deductFefo($item->product_variant_id, $this->location_id, (float) $item->quantity);

        foreach ($consumed as $entry) {
            StockMovement::record(
                batch: $entry['batch'],
                type: StockMovementType::Sale,
                quantityChange: -$entry['quantity'],
                reference: $this,
            );
        }
    }

    /**
     * Void a completed sale: restock everything it deducted and reverse any
     * customer ledger charge, as if the sale never happened. Distinct from a
     * SaleReturn — a return is goods physically coming back after a valid
     * sale; a cancellation says the sale itself shouldn't have counted.
     */
    public function cancel(): void
    {
        if ($this->status !== SaleStatus::Completed) {
            throw new RuntimeException('Only a completed sale can be cancelled.');
        }

        DB::transaction(function () {
            foreach ($this->items as $item) {
                $batch = ProductBatch::create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'location_id' => $this->location_id,
                    'batch_number' => null,
                    'quantity' => $item->quantity,
                    // Same approximation as a SaleReturn: FEFO doesn't track which
                    // original batch(es) this sale drew from, so the product's
                    // current cost is the best available stand-in.
                    'unit_cost' => $item->product?->cost_price,
                ]);

                StockMovement::record(
                    batch: $batch,
                    type: StockMovementType::SaleCancelled,
                    quantityChange: (float) $item->quantity,
                    reference: $this,
                );
            }

            if ($this->payment_method === PaymentMethod::Credit) {
                $this->customer->transactions()->create([
                    'type' => CustomerTransactionType::SaleCancelled,
                    'amount' => $this->total(),
                    'description' => "Cancelled sale {$this->sale_number}",
                    'transaction_date' => now(),
                    'reference_type' => $this->getMorphClass(),
                    'reference_id' => $this->id,
                ]);
            }

            $this->update(['status' => SaleStatus::Cancelled]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($this)
                ->withProperties(['sale_number' => $this->sale_number, 'total' => $this->total()])
                ->log("Sale {$this->sale_number} cancelled");
        });
    }
}
