<?php

namespace App\Filament\Business\Resources\StockTransfers\Pages;

use App\Enums\StockTransferStatus;
use App\Filament\Business\Resources\StockTransfers\Actions\ReceiveTransferAction;
use App\Filament\Business\Resources\StockTransfers\Actions\StockTransferWorkflowActions;
use App\Filament\Business\Resources\StockTransfers\StockTransferResource;
use App\Models\StockTransfer;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStockTransfer extends ViewRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            StockTransferWorkflowActions::submitForApproval(),
            StockTransferWorkflowActions::approve(),
            StockTransferWorkflowActions::reject(),
            StockTransferWorkflowActions::markInTransit(),
            ReceiveTransferAction::make(),
            StockTransferWorkflowActions::cancel(),
            EditAction::make()
                ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::Draft),
        ];
    }
}
