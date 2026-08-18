<?php

namespace App\Filament\Business\Widgets;

use App\Enums\StockTransferStatus;
use App\Enums\UserRole;
use App\Filament\Business\Resources\StockTransfers\StockTransferResource;
use App\Models\StockTransfer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Stock transfers currently waiting on this StoreKeeper to receive — the
 * transfer-side counterpart to PendingPurchaseOrdersWidget.
 */
class PendingStockTransfersWidget extends TableWidget
{
    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('dashboard.widgets.pending_stock_transfers_heading'))
            ->query(fn (): Builder => StockTransfer::query()
                ->whereIn('status', [StockTransferStatus::InTransit, StockTransferStatus::PartiallyReceived]))
            ->columns([
                TextColumn::make('transfer_number')
                    ->label(__('stock_transfers.columns.transfer_number')),
                TextColumn::make('fromLocation.name')
                    ->label(__('stock_transfers.fields.from_location')),
                TextColumn::make('toLocation.name')
                    ->label(__('stock_transfers.fields.to_location')),
                TextColumn::make('status')
                    ->label(__('common.fields.status'))
                    ->badge(),
            ])
            ->recordUrl(fn (StockTransfer $record) => StockTransferResource::getUrl('view', ['record' => $record]))
            ->defaultSort('transfer_date', 'asc')
            ->paginated([5, 10, 25]);
    }
}
