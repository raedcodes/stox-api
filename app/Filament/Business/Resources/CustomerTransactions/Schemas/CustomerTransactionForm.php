<?php

namespace App\Filament\Business\Resources\CustomerTransactions\Schemas;

use App\CustomerTransactionType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerTransactionForm
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
                    ->required(),
                Select::make('type')
                    ->options(CustomerTransactionType::options())
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->minValue(0.01)
                    ->required()
                    ->prefix(fn () => tenant('currency')),
                TextInput::make('description')
                    ->helperText('e.g. invoice number or payment reference.'),
                DatePicker::make('transaction_date')
                    ->required()
                    ->default(now()),
            ]);
    }
}
