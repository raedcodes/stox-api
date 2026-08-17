<?php

namespace App\Filament\Business\Resources\PurchaseOrders\Pages;

use App\Filament\Business\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrderItem;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    /**
     * Supports being deep-linked with a ?product_id= query param (e.g. the
     * "Create PO" quick action from the Low Stock dashboard widget), so the
     * product, a suggested reorder quantity, and its usual supplier/cost
     * are already on the order instead of starting from scratch.
     */
    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $this->form->fill();

        if ($productId = request()->query('product_id')) {
            $this->form->rawState($this->suggestedReorderData((int) $productId));
        }

        $this->callHook('afterFill');
    }

    /**
     * @return array<string, mixed>
     */
    protected function suggestedReorderData(int $productId): array
    {
        $product = Product::find($productId);

        if (! $product) {
            return [];
        }

        $lastItem = PurchaseOrderItem::query()
            ->where('product_id', $productId)
            ->with('purchaseOrder')
            ->latest('id')
            ->first();

        $data = [
            'items' => [
                [
                    'product_id' => $productId,
                    'product_variant_id' => ProductVariant::soleActiveIdFor($productId),
                    'quantity_ordered' => $this->suggestedReorderQuantity($product),
                    'unit_cost' => $lastItem?->unit_cost ?? $product->cost_price,
                ],
            ],
        ];

        if ($lastItem?->purchaseOrder?->supplier_id) {
            $data['supplier_id'] = $lastItem->purchaseOrder->supplier_id;
        }

        return $data;
    }

    /**
     * Suggests restocking to double the reorder point rather than exactly
     * back up to it, so the order doesn't immediately go low again after a
     * couple more sales. Null (leave blank) when the product has no
     * reorder level set, or when it's not actually low right now.
     */
    protected function suggestedReorderQuantity(Product $product): ?float
    {
        if ($product->reorder_level === null) {
            return null;
        }

        $suggested = ((float) $product->reorder_level * 2) - $product->stockOnHand(session('current_location_id'));

        return $suggested > 0 ? round($suggested, 2) : null;
    }
}
