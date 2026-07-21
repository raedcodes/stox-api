<?php

namespace App\Filament\Business\Resources\PurchaseOrders\Schemas;

use App\PurchaseOrderStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('location_id')
                    ->label('Deliver to')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('status')
                    ->options(PurchaseOrderStatus::options())
                    ->default(PurchaseOrderStatus::Draft)
                    ->required(),
                DatePicker::make('order_date')
                    ->required()
                    ->default(now()),
                DatePicker::make('expected_date')
                    ->label('Expected delivery date'),
                Textarea::make('notes')
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
                        TextInput::make('quantity_ordered')
                            ->label('Quantity')
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
                Repeater::make('charges')
                    ->label('Landed costs')
                    ->relationship()
                    ->schema([
                        TextInput::make('description')
                            ->required(),
                        TextInput::make('amount')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix(fn () => tenant('currency')),
                    ])
                    ->columns(2)
                    ->addActionLabel('Add charge')
                    ->helperText('Freight, customs, handling, or any other cost added on top of the order value.'),
            ]);
    }
}
