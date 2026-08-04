<?php

namespace App\Filament\Business\Resources\PurchaseOrders\Pages;

use App\Filament\Business\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    /**
     * Supports being deep-linked with a ?product_id= query param (e.g. the
     * "Create PO" quick action from the Low Stock dashboard widget), so the
     * product is already on the order instead of starting from scratch.
     */
    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $productId = request()->query('product_id');

        $this->form->fill($productId ? [
            'items' => [
                ['product_id' => (int) $productId],
            ],
        ] : []);

        $this->callHook('afterFill');
    }
}
