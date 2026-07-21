<?php

namespace App\Filament\Business\Resources\SupplierTransactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('supplier.name')
                            ->label('Supplier'),
                        TextEntry::make('type')
                            ->badge(),
                        TextEntry::make('amount')
                            ->money(fn () => tenant('currency')?->value),
                        TextEntry::make('transaction_date')
                            ->date(),
                        TextEntry::make('description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
