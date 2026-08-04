<?php

namespace App\Filament\Business\Resources\ProductBatches\Tables;

use App\Filament\Business\Resources\ProductBatches\ProductBatchResource;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBatch;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductBatchesTable
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
                TextColumn::make('batch_number')
                    ->label('Batch #')
                    ->searchable(),
                TextColumn::make('quantity'),
                TextColumn::make('unit_cost')
                    ->label('Unit cost')
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('manufactured_date')
                    ->label('Manufactured')
                    ->date()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expiry_date')
                    ->date()
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderByRaw("expiry_date IS NULL, expiry_date {$direction}"))
                    ->placeholder('No expiry')
                    ->color(fn (?ProductBatch $record) => match (true) {
                        $record?->expiry_date === null => 'gray',
                        $record->expiry_date->isPast() => 'danger',
                        now()->diffInDays($record->expiry_date) <= 30 => 'warning',
                        default => 'success',
                    }),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->recordUrl(fn (ProductBatch $record) => ProductBatchResource::getUrl('view', ['record' => $record]))
            ->defaultSort('expiry_date', 'asc')
            ->filters([
                Filter::make('expired')
                    ->query(fn (Builder $query) => $query->whereDate('expiry_date', '<', now())),
                Filter::make('expiring_soon')
                    ->label('Expiring within 30 days')
                    ->query(fn (Builder $query) => $query->whereBetween('expiry_date', [now(), now()->addDays(30)])),
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(fn () => Product::query()->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::query()->pluck('name', 'id')),
                TernaryFilter::make('is_active'),
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
