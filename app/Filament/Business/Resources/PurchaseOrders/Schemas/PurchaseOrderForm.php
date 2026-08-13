<?php

namespace App\Filament\Business\Resources\PurchaseOrders\Schemas;

use App\Enums\PurchaseOrderStatus;
use App\Models\ProductVariant;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('supplier_id')
                    ->label(__('common.fields.supplier'))
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('location_id')
                    ->label(__('purchase_orders.fields.deliver_to'))
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => session('current_location_id'))
                    ->required(),
                Select::make('status')
                    ->label(__('common.fields.status'))
                    ->options(PurchaseOrderStatus::options())
                    ->default(PurchaseOrderStatus::Draft->value)
                    // Read-only: every transition beyond Draft goes through
                    // the dedicated Submit/Approve/Reject/Receive/Cancel
                    // actions, not a free-form status edit — see
                    // PurchaseOrderWorkflowActions.
                    ->disabled()
                    ->required(),
                DatePicker::make('order_date')
                    ->label(__('common.fields.date'))
                    ->required()
                    ->default(now()),
                DatePicker::make('expected_date')
                    ->label(__('purchase_orders.fields.expected_date')),
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
                            ->afterStateUpdated(fn ($state, Set $set) => $set('product_variant_id', ProductVariant::soleActiveIdFor($state))),
                        Select::make('product_variant_id')
                            ->label(__('common.fields.variant'))
                            ->options(fn (Get $get) => ProductVariant::activeOptionsFor($get('product_id')))
                            ->visible(fn (Get $get) => ProductVariant::hasMultipleActiveFor($get('product_id')))
                            ->required(fn (Get $get) => ProductVariant::hasMultipleActiveFor($get('product_id')))
                            ->dehydratedWhenHidden(),
                        TextInput::make('quantity_ordered')
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
                    ->columns(4)
                    ->addActionLabel(__('common.fields.add_product'))
                    ->minItems(1)
                    ->required(),
                Repeater::make('charges')
                    ->label(__('purchase_orders.fields.landed_costs'))
                    ->relationship()
                    ->schema([
                        TextInput::make('description')
                            ->label(__('common.fields.description'))
                            ->required(),
                        TextInput::make('amount')
                            ->label(__('common.fields.amount'))
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix(fn () => tenant('currency')?->value),
                    ])
                    ->columns(2)
                    ->addActionLabel(__('purchase_orders.actions.add_charge'))
                    ->helperText(__('purchase_orders.landed_costs_helper')),
            ]);
    }
}
