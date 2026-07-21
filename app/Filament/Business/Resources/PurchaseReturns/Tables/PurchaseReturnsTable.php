<?php

namespace App\Filament\Business\Resources\PurchaseReturns\Tables;

use App\Filament\Business\Resources\PurchaseReturns\PurchaseReturnResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchaseOrder.po_number')
                    ->label('PO #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchaseOrder.supplier.name')
                    ->label('Supplier'),
                TextColumn::make('return_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('reason')
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('total')
                    ->state(fn (PurchaseReturn $record) => $record->total())
                    ->money(fn () => tenant('currency')?->value),
            ])
            ->recordUrl(fn (PurchaseReturn $record) => PurchaseReturnResource::getUrl('view', ['record' => $record]))
            ->defaultSort('return_date', 'desc')
            ->filters([
                SelectFilter::make('purchase_order_id')
                    ->label('Purchase order')
                    ->options(fn () => PurchaseOrder::query()->pluck('po_number', 'id')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
