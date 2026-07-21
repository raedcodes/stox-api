<?php

namespace App\Filament\Business\Resources\Products\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->unique(table: 'products', column: 'sku', ignoreRecord: true),
                TextInput::make('barcode')
                    ->unique(table: 'products', column: 'barcode', ignoreRecord: true),
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('unit_of_measure_id')
                    ->label('Unit')
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('min_stock_level')
                    ->label('Minimum stock level')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('The safety floor — below this, you risk running out.'),
                TextInput::make('reorder_level')
                    ->label('Reorder level')
                    ->numeric()
                    ->minValue(0)
                    ->gte('min_stock_level')
                    ->helperText('When stock drops to this level, it\'s time to reorder. Should be at or above the minimum.'),
                TextInput::make('cost_price')
                    ->numeric()
                    ->minValue(0)
                    ->prefix(fn () => tenant('currency')),
                TextInput::make('selling_price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix(fn () => tenant('currency')),
                Checkbox::make('tax_inclusive')
                    ->label('Selling price already includes tax')
                    ->default(false),
                Checkbox::make('is_active')
                    ->default(true),
            ]);
    }
}
