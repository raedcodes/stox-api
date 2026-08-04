<?php

namespace App\Models\Concerns;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Every stock-tracking/line-item model has a product_variant_id, but none of
 * the Filament forms have a variant picker yet (there's nothing to pick from
 * — no UI creates variants beyond each product's own default one). This
 * fills product_variant_id in from the selected product's default variant
 * automatically, so existing "pick a product" forms keep working unchanged.
 */
trait HasDefaultProductVariant
{
    protected static function bootHasDefaultProductVariant(): void
    {
        static::creating(function ($model) {
            if (blank($model->product_variant_id) && filled($model->product_id)) {
                $model->product_variant_id = ProductVariant::where('product_id', $model->product_id)
                    ->where('is_default', true)
                    ->value('id');
            }
        });
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
