<?php

namespace App\Filament\Business\Resources\ProductBatches\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProductBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (?string $state, Set $set) => $set('unit_cost', Product::find($state)?->cost_price))
                    ->required(),
                Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('batch_number')
                    ->label('Batch / lot number')
                    ->required(),
                TextInput::make('quantity')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('unit_cost')
                    ->label('Unit cost')
                    ->numeric()
                    ->minValue(0)
                    ->prefix(fn () => tenant('currency')?->value)
                    ->helperText('Defaults to the product\'s cost price — override if this batch\'s actual cost differs (e.g. landed costs).'),
                DatePicker::make('manufactured_date')
                    ->label('Manufactured date'),
                DatePicker::make('expiry_date')
                    ->helperText('Leave empty if this batch doesn\'t expire.'),
                Checkbox::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Turn off for a batch that\'s been written off, recalled, or fully used.'),
            ]);
    }
}
