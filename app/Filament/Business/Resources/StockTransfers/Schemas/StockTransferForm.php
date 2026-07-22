<?php

namespace App\Filament\Business\Resources\StockTransfers\Schemas;

use App\Enums\StockTransferStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('from_location_id')
                    ->label('From location')
                    ->relationship('fromLocation', 'name')
                    ->searchable()
                    ->preload()
                    ->different('to_location_id')
                    ->required(),
                Select::make('to_location_id')
                    ->label('To location')
                    ->relationship('toLocation', 'name')
                    ->searchable()
                    ->preload()
                    ->different('from_location_id')
                    ->required(),
                Select::make('status')
                    ->options(StockTransferStatus::options())
                    ->default(StockTransferStatus::Draft->value)
                    ->required(),
                DatePicker::make('transfer_date')
                    ->required()
                    ->default(now()),
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
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                    ])
                    ->columns(2)
                    ->addActionLabel('Add product')
                    ->minItems(1)
                    ->required(),
            ]);
    }
}
