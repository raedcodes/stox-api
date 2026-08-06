<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'sku',
        'barcode',
        'category_id',
        'brand_id',
        'unit_of_measure_id',
        'min_stock_level',
        'reorder_level',
        'cost_price',
        'selling_price',
        'wsp',
        'cbm',
        'tax_inclusive',
        'images',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_stock_level' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'wsp' => 'decimal:2',
            'cbm' => 'decimal:4',
            'tax_inclusive' => 'boolean',
            'images' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class)->withTrashed();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_of_measure_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    /**
     * Current on-hand quantity, optionally scoped to a location. Sourced
     * entirely from active batches — the only place stock quantity lives.
     */
    public function stockOnHand(?int $locationId = null): float
    {
        return (float) $this->batches()
            ->where('is_active', true)
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->sum('quantity');
    }

    /**
     * A category-prefixed, sequential SKU (e.g. "ELEC-001") when the user
     * leaves the field blank — the prefix is derived from the category
     * name so it stays meaningful for any category a business creates, not
     * a fixed list. Falls back to "GEN" with no category or a name with no
     * letters in it. The sequence is the highest existing number under that
     * prefix, not a plain count, so a deleted product never causes a
     * collision with the unique constraint on sku.
     */
    protected static function generateSku(?int $categoryId): string
    {
        $categoryName = $categoryId ? Category::find($categoryId)?->name : null;
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $categoryName ?? ''));
        $prefix = substr($letters, 0, 4) ?: 'GEN';

        $lastNumber = static::query()
            ->where('sku', 'like', "{$prefix}-%")
            ->pluck('sku')
            ->map(fn (string $sku) => (int) substr($sku, strlen($prefix) + 1))
            ->max() ?? 0;

        return sprintf('%s-%03d', $prefix, $lastNumber + 1);
    }

    protected static function booted(): void
    {
        static::creating(function (self $product) {
            if (blank($product->sku)) {
                $product->sku = static::generateSku($product->category_id);
            }
        });

        static::created(function (self $product) {
            $product->variants()->create([
                'is_default' => true,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'is_active' => true,
            ]);
        });

        static::updated(function (self $product) {
            // Only while the product still has just its one auto-created
            // variant — once real variants exist, the default variant may
            // have been deliberately repurposed (e.g. renamed to "Small"
            // with its own sku) and shouldn't be silently overwritten.
            if ($product->wasChanged(['sku', 'barcode']) && $product->variants()->count() === 1) {
                $product->defaultVariant?->update([
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                ]);
            }
        });
    }
}
