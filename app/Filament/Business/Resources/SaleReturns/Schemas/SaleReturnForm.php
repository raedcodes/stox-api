<?php

namespace App\Filament\Business\Resources\SaleReturns\Schemas;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SaleReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('sale_id')
                    ->label('Invoice')
                    ->options(fn () => Sale::query()
                        ->where('status', SaleStatus::Completed)
                        ->pluck('sale_number', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('return_date')
                    ->required()
                    ->default(now()),
                Textarea::make('reason')
                    ->rows(2),
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('unit_price', Product::find($state)?->selling_price);
                            }),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        TextInput::make('unit_price')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix(fn () => tenant('currency')?->value),
                    ])
                    ->columns(3)
                    ->addActionLabel('Add product')
                    ->minItems(1)
                    ->required(),
            ]);
    }
}
