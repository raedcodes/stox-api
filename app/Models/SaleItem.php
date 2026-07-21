<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_type',
        'discount_value',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount_value' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function grossAmount(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }

    public function discountAmount(): float
    {
        return \App\DiscountType::amount($this->discount_type, $this->discount_value ? (float) $this->discount_value : null, $this->grossAmount());
    }

    /**
     * Line total before tax: quantity * price, minus this line's own discount.
     */
    public function subtotal(): float
    {
        return $this->grossAmount() - $this->discountAmount();
    }

    /**
     * Tax portion of this line. If the product's price is tax-inclusive, tax
     * is extracted from the subtotal rather than added on top of it.
     */
    public function taxAmount(): float
    {
        $rate = (float) TaxSetting::current()->rate;

        if ($rate <= 0) {
            return 0;
        }

        $subtotal = $this->subtotal();

        if ($this->product?->tax_inclusive) {
            return $subtotal - ($subtotal / (1 + ($rate / 100)));
        }

        return $subtotal * ($rate / 100);
    }

    /**
     * What this line actually contributes to the invoice total: the subtotal,
     * plus tax on top unless the price already included it.
     */
    public function total(): float
    {
        return $this->product?->tax_inclusive
            ? $this->subtotal()
            : $this->subtotal() + $this->taxAmount();
    }
}
