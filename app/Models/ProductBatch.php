<?php

namespace App\Models;

use App\Models\Concerns\HasDefaultProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ProductBatch extends Model
{
    use HasDefaultProductVariant;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'location_id',
        'batch_number',
        'quantity',
        'manufactured_date',
        'expiry_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'manufactured_date' => 'date',
            'expiry_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    /**
     * Deduct a quantity of a product variant at a location across its
     * batches, first-expired-first-out. Shared by every flow that removes
     * stock (sales, purchase returns, transfers) so they all consume
     * batches the same way and can log a movement per batch actually
     * touched. Keyed by variant (not just product) so different variants
     * of the same product are tracked as separate stock.
     *
     * @return Collection<int, array{batch: ProductBatch, quantity: float}>
     */
    public static function deductFefo(int $productVariantId, int $locationId, float $quantity): Collection
    {
        $remaining = $quantity;
        $consumed = collect();

        $batches = static::where('product_variant_id', $productVariantId)
            ->where('location_id', $locationId)
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $deduct = min($remaining, (float) $batch->quantity);
            $batch->decrement('quantity', $deduct);
            $consumed->push(['batch' => $batch, 'quantity' => $deduct]);
            $remaining -= $deduct;
        }

        return $consumed;
    }
}
