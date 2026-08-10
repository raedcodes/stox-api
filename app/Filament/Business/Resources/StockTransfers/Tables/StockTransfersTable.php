<?php

namespace App\Filament\Business\Resources\StockTransfers\Tables;

use App\Enums\StockTransferStatus;
use App\Filament\Business\Resources\StockTransfers\Actions\ReceiveTransferAction;
use App\Filament\Business\Resources\StockTransfers\Actions\StockTransferWorkflowActions;
use App\Filament\Business\Resources\StockTransfers\StockTransferResource;
use App\Models\Location;
use App\Models\StockTransfer;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transfer_number')
                    ->label('Transfer #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fromLocation.name')
                    ->label('From'),
                TextColumn::make('toLocation.name')
                    ->label('To'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('transfer_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('notes')
                    ->placeholder('—')
                    ->limit(40),
            ])
            ->recordUrl(fn (StockTransfer $record) => StockTransferResource::getUrl('view', ['record' => $record]))
            ->defaultSort('transfer_date', 'desc')
            ->filters([
                SelectFilter::make('from_location_id')
                    ->label('From location')
                    ->options(fn () => Location::query()->pluck('name', 'id')),
                SelectFilter::make('to_location_id')
                    ->label('To location')
                    ->options(fn () => Location::query()->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->options(StockTransferStatus::options()),
            ])
            ->recordActions([
                StockTransferWorkflowActions::submitForApproval(),
                StockTransferWorkflowActions::approve(),
                StockTransferWorkflowActions::reject(),
                StockTransferWorkflowActions::markInTransit(),
                ReceiveTransferAction::make(),
                ActionGroup::make([
                    StockTransferWorkflowActions::cancel(),
                    EditAction::make()
                        ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::Draft),
                    DeleteAction::make()
                        ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::Draft),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
