<?php

namespace App\Filament\Business\Resources\SupplierTransactions\Schemas;

use App\Enums\SupplierTransactionType;
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
                    ->label(__('common.fields.supplier'))
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('type')
                    ->label(__('common.fields.type'))
                    ->options(SupplierTransactionType::options())
                    ->required(),
                TextInput::make('amount')
                    ->label(__('common.fields.amount'))
                    ->numeric()
                    ->minValue(0.01)
                    ->required()
                    ->prefix(fn () => tenant('currency')?->value),
                TextInput::make('description')
                    ->label(__('common.fields.description'))
                    ->helperText(__('supplier_transactions.reference_helper')),
                DatePicker::make('transaction_date')
                    ->label(__('common.fields.date'))
                    ->required()
                    ->default(now()),
            ]);
    }
}
