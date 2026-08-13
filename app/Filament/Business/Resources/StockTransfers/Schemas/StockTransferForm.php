<?php

namespace App\Filament\Business\Resources\StockTransfers\Schemas;

use App\Enums\StockTransferStatus;
use App\Models\ProductVariant;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('from_location_id')
                    ->label(__('stock_transfers.fields.from_location'))
                    ->relationship('fromLocation', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => session('current_location_id'))
                    ->different('to_location_id')
                    ->required(),
                Select::make('to_location_id')
                    ->label(__('stock_transfers.fields.to_location'))
                    ->relationship('toLocation', 'name')
                    ->searchable()
                    ->preload()
                    ->different('from_location_id')
                    ->required(),
                Select::make('status')
                    ->label(__('common.fields.status'))
                    ->options(StockTransferStatus::options())
                    ->default(StockTransferStatus::Draft->value)
                    // Read-only: every transition beyond Draft goes through
                    // the dedicated Submit/Approve/Reject/Mark In Transit/
                    // Receive/Cancel actions, which are the only things
                    // allowed to move stock — not a free-form status edit.
                    ->disabled()
                    ->required(),
                DatePicker::make('transfer_date')
                    ->label(__('common.fields.date'))
                    ->required()
                    ->default(now()),
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
                        TextInput::make('quantity')
                            ->label(__('common.fields.quantity'))
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                    ])
                    ->columns(3)
                    ->addActionLabel(__('common.fields.add_product'))
                    ->minItems(1)
                    ->required(),
            ]);
    }
}
