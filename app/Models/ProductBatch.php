<?php

namespace App\Models;

use App\Models\Concerns\HasDefaultProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ProductBatch extends Model
{
    use HasDefaultProductVariant, HasFactory;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'location_id',
        'batch_number',
        'quantity',
        'unit_cost',
        'manufactured_date',
        'expiry_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
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
     * Active batches with remaining stock that expire within the given
     * number of days. Shared by the dashboard's ExpiryAlertsWidget, the AI
     * assistant's expiring-batches tool, and the daily operations digest —
     * the AI tool's own prior version didn't filter quantity > 0, which
     * could report an already-fully-consumed batch as "expiring soon".
     */
    public function scopeExpiringWithin(Builder $query, int $days, ?int $locationId = null): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->when($locationId, fn ($q, $id) => $q->where('location_id', $id));
    }

    /**
     * Deduct a quantity of a product variant at a location across its
     * batches, first-expired-first-out. Shared by every flow that removes
     * stock (sales, purchase returns, transfers) so they all consume
     * batches the same way and can log a movement per batch actually
     * touched. Keyed by variant (not just product) so different variants
     * of the same product are tracked as separate stock.
     *
     * Locks the candidate batch rows for the duration of the caller's
     * transaction (every caller wraps this in DB::transaction()), so two
     * concurrent sales/transfers/returns hitting the same batches can't both
     * read the same "quantity available" and oversell it.
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
            ->lockForUpdate()
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
