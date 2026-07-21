<?php

namespace App\Filament\Business\Resources\SaleReturns\Schemas;

use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaleReturnInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Return details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('sale.sale_number')
                            ->label('Invoice #'),
                        TextEntry::make('sale.customer.name')
                            ->label('Customer'),
                        TextEntry::make('return_date')
                            ->date(),
                        IconEntry::make('processed_at')
                            ->label('Processed')
                            ->boolean()
                            ->state(fn (SaleReturn $record) => $record->processed_at !== null),
                        TextEntry::make('reason')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Product'),
                                TableColumn::make('Qty'),
                                TableColumn::make('Unit price'),
                                TableColumn::make('Line total'),
                            ])
                            ->schema([
                                TextEntry::make('product.name')
                                    ->hiddenLabel(),
                                TextEntry::make('quantity')
                                    ->hiddenLabel(),
                                TextEntry::make('unit_price')
                                    ->hiddenLabel()
                                    ->money(fn () => tenant('currency')?->value),
                                TextEntry::make('lineTotal')
                                    ->hiddenLabel()
                                    ->state(fn (SaleReturnItem $record) => $record->quantity * $record->unit_price)
                                    ->money(fn () => tenant('currency')?->value),
                            ]),
                    ]),
                Section::make('Total')
                    ->schema([
                        TextEntry::make('total')
                            ->label('Total')
                            ->weight('bold')
                            ->state(fn (SaleReturn $record) => $record->total())
                            ->money(fn () => tenant('currency')?->value),
                    ]),
            ]);
    }
}
