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

    /**
     * Select options for a product's active variants, labeled by
     * size/color (or "Default" when neither is set) — shared by every
     * variant-picker across POS, Purchase Orders, Stock Transfers, and
     * Stock Adjustments so they stay in sync.
     *
     * @return array<int, string>
     */
    public static function activeOptionsFor(mixed $productId): array
    {
        if (! $productId) {
            return [];
        }

        return static::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(fn (self $variant) => [
                $variant->id => collect([$variant->size, $variant->color])->filter()->implode(' / ') ?: 'Default',
            ])
            ->all();
    }

    /**
     * Whether a product has more than one active variant — the signal
     * every variant-picker uses to decide whether to show itself at all,
     * so single-variant products (the common case) see no extra field.
     */
    public static function hasMultipleActiveFor(mixed $productId): bool
    {
        if (! $productId) {
            return false;
        }

        return static::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->count() > 1;
    }

    /**
     * The id of a product's one active variant, or null if it has zero or
     * several — used to auto-fill the variant field for single-variant
     * products so staff never have to pick.
     */
    public static function soleActiveIdFor(mixed $productId): ?int
    {
        if (! $productId) {
            return null;
        }

        $variants = static::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->get();

        return $variants->count() === 1 ? $variants->first()->id : null;
    }

    /**
     * A product-sku-prefixed variant SKU when the field is left blank —
     * suffixed with the size/color that actually distinguish the variant
     * (e.g. "ELEC-001-RED-L"), or a plain sequential suffix when neither is
     * set. Mirrors Product::generateSku()'s max-based sequencing so a
     * deleted variant never causes a collision with the unique constraint.
     */
    public static function generateSku(Product $product, ?string $size, ?string $color): string
    {
        $descriptor = collect([$size, $color])
            ->filter()
            ->map(fn (string $value) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value)))
            ->filter()
            ->implode('-');

        if ($descriptor !== '') {
            return "{$product->sku}-{$descriptor}";
        }

        $lastNumber = static::query()
            ->where('product_id', $product->id)
            ->where('sku', 'like', "{$product->sku}-%")
            ->pluck('sku')
            ->map(fn (string $sku) => (int) substr($sku, strlen($product->sku) + 1))
            ->filter(fn (int $number) => $number > 0)
            ->max() ?? 1;

        return "{$product->sku}-".($lastNumber + 1);
    }

    protected static function booted(): void
    {
        static::creating(function (self $variant) {
            if (blank($variant->sku)) {
                $variant->sku = static::generateSku($variant->product, $variant->size, $variant->color);
            }

            if (blank($variant->barcode)) {
                $variant->barcode = BarcodeSetting::current()->next();
            }
        });
    }
}
