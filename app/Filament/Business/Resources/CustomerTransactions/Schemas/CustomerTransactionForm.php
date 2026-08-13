<?php

namespace App\Filament\Business\Resources\CustomerTransactions\Schemas;

use App\Enums\CustomerTransactionType;
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
                    ->label(__('common.fields.customer'))
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('type')
                    ->label(__('common.fields.type'))
                    ->options(CustomerTransactionType::options())
                    ->required(),
                TextInput::make('amount')
                    ->label(__('common.fields.amount'))
                    ->numeric()
                    ->minValue(0.01)
                    ->required()
                    ->prefix(fn () => tenant('currency')?->value),
                TextInput::make('description')
                    ->label(__('common.fields.description'))
                    ->helperText(__('customer_transactions.reference_helper')),
                DatePicker::make('transaction_date')
                    ->label(__('common.fields.date'))
                    ->required()
                    ->default(now()),
            ]);
    }
}
