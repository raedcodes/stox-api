<?php

namespace App\Filament\Business\Resources\PurchaseOrders\Tables;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Business\Resources\PurchaseOrders\Actions\ReceiveStockAction;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->label('PO #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                TextColumn::make('location.name')
                    ->label('Deliver to'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('order_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('expected_date')
                    ->date()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->state(fn (PurchaseOrder $record) => $record->total())
                    ->money(fn () => tenant('currency')?->value),
            ])
            ->defaultSort('order_date', 'desc')
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->options(fn () => Supplier::query()->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::query()->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->options(PurchaseOrderStatus::options()),
            ])
            ->recordActions([
                ReceiveStockAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
