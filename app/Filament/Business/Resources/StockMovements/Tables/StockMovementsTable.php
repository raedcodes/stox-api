<?php

namespace App\Filament\Business\Resources\StockMovements\Tables;

use App\Enums\StockMovementType;
use App\Filament\Business\Resources\StockMovements\StockMovementResource;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('common.fields.date'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label(__('common.fields.product'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label(__('common.fields.location')),
                TextColumn::make('batch.batch_number')
                    ->label(__('common.fields.batch'))
                    ->placeholder(__('common.fields.no_batch_number')),
                TextColumn::make('type')
                    ->label(__('common.fields.type'))
                    ->badge(),
                TextColumn::make('quantity_change')
                    ->label(__('common.fields.quantity'))
                    ->color(fn (StockMovement $record) => $record->quantity_change >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn (StockMovement $record) => ($record->quantity_change >= 0 ? '+' : '').rtrim(rtrim(number_format($record->quantity_change, 2), '0'), '.')),
                TextColumn::make('quantity_after')
                    ->label(__('stock_movements.fields.quantity_after')),
                TextColumn::make('referenceLabel')
                    ->label(__('common.fields.reference'))
                    ->state(fn (StockMovement $record) => $record->referenceLabel())
                    ->placeholder('—'),
                TextColumn::make('performedBy.name')
                    ->label(__('common.fields.by'))
                    ->placeholder(__('common.fields.system')),
            ])
            ->recordUrl(fn (StockMovement $record) => StockMovementResource::getUrl('view', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('product_id')
                    ->label(__('common.fields.product'))
                    ->options(fn () => Product::query()->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label(__('common.fields.location'))
                    ->options(fn () => Location::query()->pluck('name', 'id')),
                SelectFilter::make('type')
                    ->label(__('common.fields.type'))
                    ->options(StockMovementType::options()),
            ]);
    }
}
