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
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
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
        'creator_type',
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
                $sale->created_by = auth()->user()?->getKey();
                $sale->creator_type = auth()->user()?->getMorphClass();
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

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function cashier(): MorphTo
    {
        return $this->morphTo(name: 'creator', type: 'creator_type', id: 'created_by');
    }

    /**
     * The sale's actual tender breakdown: real sale_payments rows if any
     * exist, else a single line synthesized from the legacy payment_method
     * column — mirrors SaleItem::taxAmount()/cogsAmount()'s frozen-value-
     * with-fallback pattern, so a sale created before split payments
     * existed still reads correctly everywhere this is used.
     *
     * @return Collection<int, SalePayment>
     */
    public function paymentLines(): Collection
    {
        if ($this->payments->isNotEmpty()) {
            return $this->payments;
        }

        if (! $this->payment_method) {
            return collect();
        }

        return collect([new SalePayment([
            'payment_method' => $this->payment_method,
            'amount' => $this->total(),
        ])]);
    }

    /**
     * How much of this sale is on the customer's account — the only
     * portion that ever touches the customer ledger or a credit-limit
     * check, since Cash/Card lines settle immediately.
     */
    public function creditAmount(): float
    {
        return (float) $this->paymentLines()
            ->where('payment_method', PaymentMethod::Credit)
            ->sum(fn (SalePayment $payment) => (float) $payment->amount);
    }

    /**
     * Short display label for tables/infolists — "Cash", or "Cash + Card"
     * for a split sale — in place of rendering the (possibly stale once
     * split) payment_method column directly.
     */
    public function paymentSummary(): string
    {
        $methods = $this->paymentLines()
            ->pluck('payment_method')
            ->unique()
            ->map(fn (PaymentMethod $method) => $method->getLabel());

        return $methods->isNotEmpty() ? $methods->implode(' + ') : '—';
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

        $creditAmount = $this->creditAmount();

        if ($creditAmount > 0) {
            $creditLimit = $this->customer->credit_limit;

            if ($creditLimit !== null && ($this->customer->balance() + $creditAmount) > (float) $creditLimit) {
                throw new RuntimeException(
                    "This sale would put {$this->customer->name} over their credit limit ".
                    "(currently owed: {$this->customer->balance()}, limit: {$creditLimit})."
                );
            }
        }

        DB::transaction(function () use ($creditAmount) {
            $taxRate = (float) TaxSetting::current()->rate;

            foreach ($this->items as $item) {
                $item->update([
                    'tax_rate' => $taxRate,
                    'unit_cost' => $this->deductStock($item),
                ]);
            }

            if ($creditAmount > 0) {
                $this->customer->transactions()->create([
                    'type' => CustomerTransactionType::CreditSale,
                    'amount' => $creditAmount,
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

    /**
     * Deducts stock FEFO and returns the weighted-average cost of the
     * batches actually consumed, to be frozen onto the SaleItem — more
     * accurate than the product's current cost_price alone, since a line's
     * stock may have been drawn from batches bought at different costs.
     */
    protected function deductStock(SaleItem $item): float
    {
        $consumed = ProductBatch::deductFefo($item->product_variant_id, $this->location_id, (float) $item->quantity);

        $totalCost = 0.0;
        $totalQuantity = 0.0;

        foreach ($consumed as $entry) {
            StockMovement::record(
                batch: $entry['batch'],
                type: StockMovementType::Sale,
                quantityChange: -$entry['quantity'],
                reference: $this,
            );

            $totalCost += $entry['quantity'] * (float) $entry['batch']->unit_cost;
            $totalQuantity += $entry['quantity'];
        }

        return $totalQuantity > 0 ? $totalCost / $totalQuantity : (float) $item->product?->cost_price;
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

            $creditAmount = $this->creditAmount();

            if ($creditAmount > 0) {
                $this->customer->transactions()->create([
                    'type' => CustomerTransactionType::SaleCancelled,
                    'amount' => $creditAmount,
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
