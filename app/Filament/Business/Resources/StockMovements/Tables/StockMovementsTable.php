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
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Location'),
                TextColumn::make('batch.batch_number')
                    ->label('Batch')
                    ->placeholder('No batch #'),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('quantity_change')
                    ->label('Quantity')
                    ->color(fn (StockMovement $record) => $record->quantity_change >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn (StockMovement $record) => ($record->quantity_change >= 0 ? '+' : '').rtrim(rtrim(number_format($record->quantity_change, 2), '0'), '.')),
                TextColumn::make('quantity_after')
                    ->label('Balance after'),
                TextColumn::make('referenceLabel')
                    ->label('Reference')
                    ->state(fn (StockMovement $record) => $record->referenceLabel())
                    ->placeholder('—'),
                TextColumn::make('performedBy.name')
                    ->label('By')
                    ->placeholder('System'),
            ])
            ->recordUrl(fn (StockMovement $record) => StockMovementResource::getUrl('view', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(fn () => Product::query()->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::query()->pluck('name', 'id')),
                SelectFilter::make('type')
                    ->options(StockMovementType::options()),
            ]);
    }
}
