<?php

namespace App\Filament\Business\Resources\SupplierTransactions\Schemas;

use App\Models\SupplierTransaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('supplier_transactions.section_title'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('transaction_number')
                            ->label(__('common.fields.transaction_number')),
                        TextEntry::make('supplier.name')
                            ->label(__('common.fields.supplier')),
                        TextEntry::make('type')
                            ->label(__('common.fields.type'))
                            ->badge(),
                        TextEntry::make('amount')
                            ->label(__('common.fields.amount'))
                            ->money(fn () => tenant('currency')?->value),
                        TextEntry::make('referenceLabel')
                            ->label(__('common.fields.reference'))
                            ->state(fn (SupplierTransaction $record) => $record->referenceLabel())
                            ->placeholder('—'),
                        TextEntry::make('transaction_date')
                            ->label(__('common.fields.date'))
                            ->date(),
                        TextEntry::make('description')
                            ->label(__('common.fields.description'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
