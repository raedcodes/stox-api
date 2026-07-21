<?php

namespace App\Filament\Business\Resources\SupplierTransactions\Schemas;

use App\SupplierTransactionType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupplierTransactionForm
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
                Select::make('type')
                    ->options(SupplierTransactionType::options())
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
