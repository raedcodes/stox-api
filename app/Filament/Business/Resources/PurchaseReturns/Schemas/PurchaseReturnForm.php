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
                    ->label(__('purchase_returns.fields.purchase_order'))
                    ->relationship('purchaseOrder', 'po_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('return_date')
                    ->label(__('common.fields.date'))
                    ->required()
                    ->default(now()),
                Textarea::make('reason')
                    ->label(__('common.fields.reason'))
                    ->rows(2),
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Select::make('product_id')
                            ->label(__('common.fields.product'))
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('quantity')
                            ->label(__('common.fields.quantity'))
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        TextInput::make('unit_cost')
                            ->label(__('common.fields.unit_cost'))
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix(fn () => tenant('currency')?->value),
                    ])
                    ->columns(3)
                    ->addActionLabel(__('common.fields.add_product'))
                    ->minItems(1)
                    ->required(),
            ]);
    }
}
