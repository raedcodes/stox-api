<?php

namespace App\Filament\Business\Resources\PurchaseOrders\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Business\Resources\PurchaseOrders\Actions\PurchaseOrderWorkflowActions;
use App\Filament\Business\Resources\PurchaseOrders\Actions\ReceiveStockAction;
use App\Filament\Business\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PurchaseOrderWorkflowActions::submitForApproval(),
            PurchaseOrderWorkflowActions::approve(),
            PurchaseOrderWorkflowActions::reject(),
            ReceiveStockAction::make(),
            PurchaseOrderWorkflowActions::cancel(),
            EditAction::make()
                ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::Draft),
        ];
    }
}
