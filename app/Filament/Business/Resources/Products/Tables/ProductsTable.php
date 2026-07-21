<?php

namespace App\Filament\Business\Resources\Products\Tables;

use App\Models\Brand;
use App\Models\Category;
use App\Models\UnitOfMeasure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('images')
                    ->label('')
                    ->limit(1)
                    ->circular(false),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('—'),
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->placeholder('—'),
                TextColumn::make('unit.abbreviation')
                    ->label('Unit'),
                TextColumn::make('min_stock_level')
                    ->label('Min')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reorder_level')
                    ->label('Reorder at')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cost_price')
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('selling_price')
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder('—'),
                IconColumn::make('tax_inclusive')
                    ->label('Tax incl.')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::query()->pluck('name', 'id')),
                SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->options(fn () => Brand::query()->pluck('name', 'id')),
                SelectFilter::make('unit_of_measure_id')
                    ->label('Unit')
                    ->options(fn () => UnitOfMeasure::query()->pluck('name', 'id')),
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
