<?php

namespace App\Filament\Business\Resources\StockAdjustments\Tables;

use App\Enums\StockAdjustmentReason;
use App\Filament\Business\Resources\StockAdjustments\StockAdjustmentResource;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockAdjustment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockAdjustmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Location'),
                TextColumn::make('batch.batch_number')
                    ->label('Batch')
                    ->placeholder('No batch #'),
                TextColumn::make('quantity_change')
                    ->label('Quantity')
                    ->color(fn (StockAdjustment $record) => $record->quantity_change >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn (StockAdjustment $record) => ($record->quantity_change >= 0 ? '+' : '').rtrim(rtrim(number_format($record->quantity_change, 2), '0'), '.')),
                TextColumn::make('reason')
                    ->badge(),
                TextColumn::make('adjustment_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('notes')
                    ->placeholder('—')
                    ->limit(40),
            ])
            ->recordUrl(fn (StockAdjustment $record) => StockAdjustmentResource::getUrl('view', ['record' => $record]))
            ->defaultSort('adjustment_date', 'desc')
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(fn () => Product::query()->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::query()->pluck('name', 'id')),
                SelectFilter::make('reason')
                    ->options(StockAdjustmentReason::options()),
            ])
            ->recordActions([
                //
            ]);
    }
}
