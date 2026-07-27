<?php

namespace App\Filament\Business\Resources\Sales\Schemas;

use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sale details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('sale_number')
                            ->label('Invoice #'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('payment_method')
                            ->placeholder('—'),
                        TextEntry::make('customer.name')
                            ->label('Customer'),
                        TextEntry::make('location.name')
                            ->label('Location'),
                        TextEntry::make('sale_date')
                            ->dateTime(),
                        TextEntry::make('cashier.name')
                            ->label('Cashier')
                            ->placeholder('—'),
                        TextEntry::make('notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Totals')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('subtotal')
                            ->state(fn (Sale $record) => $record->subtotal())
                            ->money(fn () => tenant('currency')?->value),
                        TextEntry::make('taxTotal')
                            ->label('Tax')
                            ->state(fn (Sale $record) => $record->taxTotal())
                            ->money(fn () => tenant('currency')?->value),
                        TextEntry::make('invoiceDiscountAmount')
                            ->label('Invoice discount')
                            ->state(fn (Sale $record) => $record->invoiceDiscountAmount())
                            ->money(fn () => tenant('currency')?->value),
                        TextEntry::make('total')
                            ->label('Total')
                            ->weight('bold')
                            ->state(fn (Sale $record) => $record->total())
                            ->money(fn () => tenant('currency')?->value),
                    ]),
                Section::make('Items')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Product'),
                                TableColumn::make('Qty'),
                                TableColumn::make('Unit price'),
                                TableColumn::make('Discount'),
                                TableColumn::make('Tax'),
                                TableColumn::make('Line total'),
                            ])
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Product'),
                                TextEntry::make('quantity')
                                    ->label('Qty'),
                                TextEntry::make('unit_price')
                                    ->label('Unit price')
                                    ->money(fn () => tenant('currency')?->value),
                                TextEntry::make('discountAmount')
                                    ->label('Discount')
                                    ->state(fn (SaleItem $record) => $record->discountAmount())
                                    ->money(fn () => tenant('currency')?->value),
                                TextEntry::make('taxAmount')
                                    ->label('Tax')
                                    ->state(fn (SaleItem $record) => $record->taxAmount())
                                    ->money(fn () => tenant('currency')?->value),
                                TextEntry::make('total')
                                    ->label('Line total')
                                    ->state(fn (SaleItem $record) => $record->total())
                                    ->money(fn () => tenant('currency')?->value),
                            ]),
                    ]),
            ]);
    }
}
