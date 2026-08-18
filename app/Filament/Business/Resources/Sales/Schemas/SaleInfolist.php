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
                Section::make(__('sales.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('sale_number')
                            ->label(__('common.fields.invoice_number')),
                        TextEntry::make('status')
                            ->label(__('common.fields.status'))
                            ->badge(),
                        TextEntry::make('payment_summary')
                            ->label(__('common.fields.payment_method'))
                            ->state(fn (Sale $record) => $record->paymentSummary()),
                        TextEntry::make('customer.name')
                            ->label(__('common.fields.customer')),
                        TextEntry::make('location.name')
                            ->label(__('common.fields.location')),
                        TextEntry::make('sale_date')
                            ->label(__('common.fields.date'))
                            ->dateTime(),
                        TextEntry::make('cashier.name')
                            ->label(__('common.fields.cashier'))
                            ->placeholder('—'),
                        TextEntry::make('notes')
                            ->label(__('common.fields.notes'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('sales.totals_section_title'))
                    ->columns(4)
                    ->schema([
                        TextEntry::make('subtotal')
                            ->label(__('common.fields.subtotal'))
                            ->state(fn (Sale $record) => $record->subtotal())
                            ->money(fn () => tenant('currency')?->value),
                        TextEntry::make('taxTotal')
                            ->label(__('common.fields.tax'))
                            ->state(fn (Sale $record) => $record->taxTotal())
                            ->money(fn () => tenant('currency')?->value),
                        TextEntry::make('invoiceDiscountAmount')
                            ->label(__('sales.fields.invoice_discount'))
                            ->state(fn (Sale $record) => $record->invoiceDiscountAmount())
                            ->money(fn () => tenant('currency')?->value),
                        TextEntry::make('total')
                            ->label(__('common.fields.total'))
                            ->weight('bold')
                            ->state(fn (Sale $record) => $record->total())
                            ->money(fn () => tenant('currency')?->value),
                    ]),
                Section::make(__('sales.items_section_title'))
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('common.fields.product')),
                                TableColumn::make(__('common.fields.qty')),
                                TableColumn::make(__('common.fields.unit_price')),
                                TableColumn::make(__('common.fields.discount')),
                                TableColumn::make(__('common.fields.tax')),
                                TableColumn::make(__('common.fields.line_total')),
                            ])
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label(__('common.fields.product')),
                                TextEntry::make('quantity')
                                    ->label(__('common.fields.qty')),
                                TextEntry::make('unit_price')
                                    ->label(__('common.fields.unit_price'))
                                    ->money(fn () => tenant('currency')?->value),
                                TextEntry::make('discountAmount')
                                    ->label(__('common.fields.discount'))
                                    ->state(fn (SaleItem $record) => $record->discountAmount())
                                    ->money(fn () => tenant('currency')?->value),
                                TextEntry::make('taxAmount')
                                    ->label(__('common.fields.tax'))
                                    ->state(fn (SaleItem $record) => $record->taxAmount())
                                    ->money(fn () => tenant('currency')?->value),
                                TextEntry::make('total')
                                    ->label(__('common.fields.line_total'))
                                    ->state(fn (SaleItem $record) => $record->total())
                                    ->money(fn () => tenant('currency')?->value),
                            ]),
                    ]),
            ]);
    }
}
