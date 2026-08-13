<?php

namespace App\Filament\Business\Resources\Products\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('common.fields.name'))
                    ->required(),
                TextInput::make('sku')
                    ->label(__('common.fields.sku'))
                    // Only required on edit — Product::generateSku() fills
                    // it in automatically on create if left blank.
                    ->required(fn (string $operation): bool => $operation === 'edit')
                    ->unique(table: 'products', column: 'sku', ignoreRecord: true)
                    ->helperText(fn (string $operation): ?string => $operation === 'create'
                        ? __('products.sku_helper_create')
                        : null),
                TextInput::make('barcode')
                    ->label(__('common.fields.barcode'))
                    ->unique(table: 'products', column: 'barcode', ignoreRecord: true),
                Textarea::make('description')
                    ->label(__('common.fields.description'))
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('category_id')
                    ->label(__('common.fields.category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('brand_id')
                    ->label(__('common.fields.brand'))
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('unit_of_measure_id')
                    ->label(__('products.fields.unit'))
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('min_stock_level')
                    ->label(__('products.fields.min_stock_level'))
                    ->numeric()
                    ->minValue(0)
                    ->helperText(__('products.min_stock_level_helper')),
                TextInput::make('reorder_level')
                    ->label(__('products.fields.reorder_level'))
                    ->numeric()
                    ->minValue(0)
                    ->gte('min_stock_level')
                    ->helperText(__('products.reorder_level_helper')),
                TextInput::make('cost_price')
                    ->label(__('products.fields.cost_price'))
                    ->numeric()
                    ->minValue(0)
                    ->prefix(fn () => tenant('currency')?->value),
                TextInput::make('selling_price')
                    ->label(__('products.fields.selling_price'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix(fn () => tenant('currency')?->value),
                TextInput::make('wsp')
                    ->label(__('products.fields.wsp'))
                    ->numeric()
                    ->minValue(0)
                    ->prefix(fn () => tenant('currency')?->value)
                    ->helperText(__('products.wsp_helper')),
                TextInput::make('cbm')
                    ->label(__('products.fields.cbm'))
                    ->numeric()
                    ->minValue(0)
                    ->helperText(__('products.cbm_helper')),
                Checkbox::make('tax_inclusive')
                    ->label(__('products.fields.tax_inclusive'))
                    ->default(false),
                FileUpload::make('images')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->directory(fn () => 'product-images/' . tenant('id'))
                    ->visibility('public')
                    ->helperText(__('products.images_helper')),
                Checkbox::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->default(true),
            ]);
    }
}
