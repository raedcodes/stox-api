<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'is_default',
        'sku',
        'barcode',
        'size',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    /**
     * Current on-hand quantity for this specific variant, optionally scoped
     * to a location. Mirrors Product::stockOnHand() but at variant
     * granularity — the capability variants didn't have before.
     */
    public function stockOnHand(?int $locationId = null): float
    {
        return (float) $this->batches()
            ->where('is_active', true)
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->sum('quantity');
    }
}
