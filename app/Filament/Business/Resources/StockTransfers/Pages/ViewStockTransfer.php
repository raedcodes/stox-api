<?php

namespace App\Filament\Business\Resources\StockTransfers\Pages;

use App\Enums\StockTransferStatus;
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
            EditAction::make()
                ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::Draft),
        ];
    }
}
