<?php

namespace App\Filament\Business\Resources\PurchaseReturns\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('purchase_order_id')
                    ->label('Purchase order')
                    ->relationship('purchaseOrder', 'po_number')
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
                            ->required(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        TextInput::make('unit_cost')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix(fn () => tenant('currency')),
                    ])
                    ->columns(3)
                    ->addActionLabel('Add product')
                    ->minItems(1)
                    ->required(),
            ]);
    }
}
