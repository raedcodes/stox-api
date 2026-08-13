<?php

namespace App\Filament\Business\Resources\Sales\Schemas;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\TaxSetting;
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
                    ->label(__('common.fields.customer'))
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => Customer::walkIn()->id)
                    ->required(),
                Select::make('location_id')
                    ->label(__('common.fields.location'))
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('status')
                    ->label(__('common.fields.status'))
                    ->options([SaleStatus::Draft->value => SaleStatus::Draft->getLabel()])
                    ->default(SaleStatus::Draft->value)
                    ->required()
                    ->disabled()
                    ->helperText(__('sales.status_helper')),
                Select::make('payment_method')
                    ->label(__('common.fields.payment_method'))
                    ->options(PaymentMethod::options())
                    ->required(),
                DatePicker::make('sale_date')
                    ->label(__('common.fields.date'))
                    ->required()
                    ->default(now()),
                TextInput::make('tax_rate_display')
                    ->label(__('sales.fields.tax_rate'))
                    ->disabled()
                    ->dehydrated(false)
                    ->default(fn () => number_format((float) TaxSetting::current()->rate, 2).'%')
                    ->helperText(__('sales.tax_rate_helper')),
                Select::make('discount_type')
                    ->label(__('sales.fields.invoice_discount_type'))
                    ->options(DiscountType::options()),
                TextInput::make('discount_value')
                    ->label(__('sales.fields.invoice_discount_value'))
                    ->numeric()
                    ->minValue(0),
                Textarea::make('notes')
                    ->label(__('common.fields.notes'))
                    ->rows(2),
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Select::make('product_id')
                            ->label(__('common.fields.product'))
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('unit_price', Product::find($state)?->selling_price);
                            }),
                        TextInput::make('quantity')
                            ->label(__('common.fields.quantity'))
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        TextInput::make('unit_price')
                            ->label(__('common.fields.unit_price'))
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix(fn () => tenant('currency')?->value),
                        Select::make('discount_type')
                            ->label(__('common.fields.discount_type'))
                            ->options(DiscountType::options()),
                        TextInput::make('discount_value')
                            ->label(__('common.fields.discount_value'))
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->columns(5)
                    ->columnSpan(2)
                    ->addActionLabel(__('common.fields.add_product'))
                    ->minItems(1)
                    ->required(),
            ]);
    }
}
