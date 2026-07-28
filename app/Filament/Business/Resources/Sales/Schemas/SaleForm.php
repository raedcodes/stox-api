<?php

namespace App\Filament\Business\Resources\Sales\Schemas;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => Customer::walkIn()->id)
                    ->required(),
                Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('status')
                    ->options([SaleStatus::Draft->value => SaleStatus::Draft->getLabel()])
                    ->default(SaleStatus::Draft->value)
                    ->required()
                    ->disabled()
                    ->helperText('Use the "Complete Sale" action to finalize a sale — this deducts stock and updates the customer ledger.'),
                Select::make('payment_method')
                    ->options(PaymentMethod::options())
                    ->required(),
                DatePicker::make('sale_date')
                    ->required()
                    ->default(now()),
                Select::make('discount_type')
                    ->label('Invoice discount type')
                    ->options(DiscountType::options()),
                TextInput::make('discount_value')
                    ->label('Invoice discount value')
                    ->numeric()
                    ->minValue(0),
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
                        Select::make('discount_type')
                            ->label('Discount type')
                            ->options(DiscountType::options()),
                        TextInput::make('discount_value')
                            ->label('Discount value')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->columns(5)
                    ->columnSpan(2)
                    ->addActionLabel('Add product')
                    ->minItems(1)
                    ->required(),
            ]);
    }
}
