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
                Section::make(__('sale_returns.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('sale.sale_number')
                            ->label(__('common.fields.invoice_number')),
                        TextEntry::make('sale.customer.name')
                            ->label(__('common.fields.customer')),
                        TextEntry::make('return_date')
                            ->label(__('common.fields.date'))
                            ->date(),
                        IconEntry::make('processed_at')
                            ->label(__('common.fields.processed'))
                            ->boolean()
                            ->state(fn (SaleReturn $record) => $record->processed_at !== null),
                        TextEntry::make('reason')
                            ->label(__('common.fields.reason'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('common.fields.total'))
                    ->schema([
                        TextEntry::make('total')
                            ->label(__('common.fields.total'))
                            ->weight('bold')
                            ->state(fn (SaleReturn $record) => $record->total())
                            ->money(fn () => tenant('currency')?->value),
                    ]),
                Section::make(__('sale_returns.items_section_title'))
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('common.fields.product')),
                                TableColumn::make(__('common.fields.qty')),
                                TableColumn::make(__('common.fields.unit_price')),
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
                                TextEntry::make('lineTotal')
                                    ->label(__('common.fields.line_total'))
                                    ->state(fn (SaleReturnItem $record) => $record->quantity * $record->unit_price)
                                    ->money(fn () => tenant('currency')?->value),
                            ]),
                    ]),

            ]);
    }
}
