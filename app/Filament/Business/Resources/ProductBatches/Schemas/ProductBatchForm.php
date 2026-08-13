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
                    ->label(__('common.fields.product'))
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (?string $state, Set $set) => $set('unit_cost', Product::find($state)?->cost_price))
                    ->required(),
                Select::make('location_id')
                    ->label(__('common.fields.location'))
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('batch_number')
                    ->label(__('product_batches.fields.batch_number'))
                    ->required(),
                TextInput::make('quantity')
                    ->label(__('common.fields.quantity'))
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('unit_cost')
                    ->label(__('product_batches.fields.unit_cost'))
                    ->numeric()
                    ->minValue(0)
                    ->prefix(fn () => tenant('currency')?->value)
                    ->helperText(__('product_batches.unit_cost_helper')),
                DatePicker::make('manufactured_date')
                    ->label(__('product_batches.fields.manufactured_date')),
                DatePicker::make('expiry_date')
                    ->label(__('product_batches.fields.expiry_date'))
                    ->helperText(__('product_batches.expiry_date_helper')),
                Checkbox::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->default(true)
                    ->helperText(__('product_batches.is_active_helper')),
            ]);
    }
}
