<?php

namespace App\Filament\Business\Resources\CustomerTransactions\Schemas;

use App\Models\CustomerTransaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('transaction_number')
                            ->label('Transaction #'),
                        TextEntry::make('customer.name')
                            ->label('Customer'),
                        TextEntry::make('type')
                            ->badge(),
                        TextEntry::make('amount')
                            ->money(fn () => tenant('currency')?->value),
                        TextEntry::make('referenceLabel')
                            ->label('Reference')
                            ->state(fn (CustomerTransaction $record) => $record->referenceLabel())
                            ->placeholder('—'),
                        TextEntry::make('transaction_date')
                            ->dateTime(),
                        TextEntry::make('description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
