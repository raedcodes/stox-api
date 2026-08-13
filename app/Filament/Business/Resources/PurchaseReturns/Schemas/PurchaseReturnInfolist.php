<?php

namespace App\Filament\Business\Resources\PurchaseReturns\Schemas;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseReturnInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('purchase_returns.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('purchaseOrder.po_number')
                            ->label(__('common.fields.po_number')),
                        TextEntry::make('purchaseOrder.supplier.name')
                            ->label(__('common.fields.supplier')),
                        TextEntry::make('return_date')
                            ->label(__('common.fields.date'))
                            ->date(),
                        IconEntry::make('processed_at')
                            ->label(__('common.fields.processed'))
                            ->boolean()
                            ->state(fn (PurchaseReturn $record) => $record->processed_at !== null),
                        TextEntry::make('reason')
                            ->label(__('common.fields.reason'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('purchase_returns.items_section_title'))
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('common.fields.product')),
                                TableColumn::make(__('common.fields.qty')),
                                TableColumn::make(__('common.fields.unit_cost')),
                                TableColumn::make(__('common.fields.line_total')),
                            ])
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label(__('common.fields.product')),
                                TextEntry::make('quantity')
                                    ->label(__('common.fields.qty')),
                                TextEntry::make('unit_cost')
                                    ->label(__('common.fields.unit_cost'))
                                    ->money(fn () => tenant('currency')?->value),
                                TextEntry::make('lineTotal')
                                    ->label(__('common.fields.line_total'))
                                    ->state(fn (PurchaseReturnItem $record) => $record->quantity * $record->unit_cost)
                                    ->money(fn () => tenant('currency')?->value),
                            ]),
                    ]),
                Section::make(__('common.fields.total'))
                    ->schema([
                        TextEntry::make('total')
                            ->label(__('common.fields.total'))
                            ->weight('bold')
                            ->state(fn (PurchaseReturn $record) => $record->total())
                            ->money(fn () => tenant('currency')?->value),
                    ]),
            ]);
    }
}
