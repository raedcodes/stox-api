<?php

namespace App\Filament\Business\Resources\ProductBatches\Tables;

use App\Filament\Business\Resources\ProductBatches\ProductBatchResource;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBatch;
use Filament\Actions\ActionGroup;
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
                    ->label(__('common.fields.product'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label(__('common.fields.location')),
                TextColumn::make('batch_number')
                    ->label(__('product_batches.columns.batch_hash'))
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label(__('common.fields.quantity')),
                TextColumn::make('unit_cost')
                    ->label(__('product_batches.fields.unit_cost'))
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('manufactured_date')
                    ->label(__('product_batches.columns.manufactured'))
                    ->date()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expiry_date')
                    ->label(__('product_batches.fields.expiry_date'))
                    ->date()
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderByRaw(
                        'expiry_date IS NULL, expiry_date '.(strtolower($direction) === 'desc' ? 'desc' : 'asc')
                    ))
                    ->placeholder(__('product_batches.no_expiry'))
                    ->color(fn (?ProductBatch $record) => match (true) {
                        $record?->expiry_date === null => 'gray',
                        $record->expiry_date->isPast() => 'danger',
                        now()->diffInDays($record->expiry_date) <= 30 => 'warning',
                        default => 'success',
                    }),
                IconColumn::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->boolean(),
            ])
            ->recordUrl(fn (ProductBatch $record) => ProductBatchResource::getUrl('view', ['record' => $record]))
            ->defaultSort('expiry_date', 'asc')
            ->filters([
                Filter::make('expired')
                    ->label(__('product_batches.filters.expired'))
                    ->query(fn (Builder $query) => $query->whereDate('expiry_date', '<', now())),
                Filter::make('expiring_soon')
                    ->label(__('product_batches.filters.expiring_soon'))
                    ->query(fn (Builder $query) => $query->whereBetween('expiry_date', [now(), now()->addDays(30)])),
                SelectFilter::make('product_id')
                    ->label(__('common.fields.product'))
                    ->options(fn () => Product::query()->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label(__('common.fields.location'))
                    ->options(fn () => Location::query()->pluck('name', 'id')),
                TernaryFilter::make('is_active')
                    ->label(__('common.fields.is_active')),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
