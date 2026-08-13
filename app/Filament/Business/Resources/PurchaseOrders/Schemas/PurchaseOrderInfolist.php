<?php

namespace App\Filament\Business\Resources\PurchaseOrders\Schemas;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('purchase_orders.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('po_number')
                            ->label(__('common.fields.po_number')),
                        TextEntry::make('status')
                            ->label(__('common.fields.status'))
                            ->badge(),
                        TextEntry::make('supplier.name')
                            ->label(__('common.fields.supplier')),
                        TextEntry::make('location.name')
                            ->label(__('purchase_orders.fields.deliver_to')),
                        TextEntry::make('order_date')
                            ->label(__('common.fields.date'))
                            ->date(),
                        TextEntry::make('expected_date')
                            ->label(__('purchase_orders.fields.expected_date'))
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('notes')
                            ->label(__('common.fields.notes'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('purchase_orders.items_section_title'))
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('common.fields.product')),
                                TableColumn::make(__('purchase_orders.fields.ordered')),
                                TableColumn::make(__('purchase_orders.fields.received')),
                                TableColumn::make(__('common.fields.unit_cost')),
                                TableColumn::make(__('common.fields.line_total')),
                            ])
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label(__('common.fields.product')),
                                TextEntry::make('quantity_ordered')
                                    ->label(__('purchase_orders.fields.ordered')),
                                TextEntry::make('quantity_received')
                                    ->label(__('purchase_orders.fields.received')),
                                TextEntry::make('unit_cost')
                                    ->label(__('common.fields.unit_cost'))
                                    ->money(fn () => tenant('currency')?->value),
                                TextEntry::make('lineTotal')
                                    ->label(__('common.fields.line_total'))
                                    ->state(fn (PurchaseOrderItem $record) => $record->quantity_ordered * $record->unit_cost)
                                    ->money(fn () => tenant('currency')?->value),
                            ]),
                    ]),
                Section::make(__('purchase_orders.landed_costs_section_title'))
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('charges')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('common.fields.description')),
                                TableColumn::make(__('common.fields.amount')),
                            ])
                            ->schema([
                                TextEntry::make('description')
                                    ->label(__('common.fields.description')),
                                TextEntry::make('amount')
                                    ->label(__('common.fields.amount'))
                                    ->money(fn () => tenant('currency')?->value),
                            ]),
                    ])
                    ->visible(fn (PurchaseOrder $record) => $record->charges()->exists()),
                Section::make(__('purchase_orders.totals_section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('subtotal')
                            ->label(__('common.fields.subtotal'))
                            ->state(fn (PurchaseOrder $record) => $record->subtotal())
                            ->money(fn () => tenant('currency')?->value),
                        TextEntry::make('landedCostTotal')
                            ->label(__('purchase_orders.fields.landed_costs'))
                            ->state(fn (PurchaseOrder $record) => $record->landedCostTotal())
                            ->money(fn () => tenant('currency')?->value),
                        TextEntry::make('total')
                            ->label(__('common.fields.total'))
                            ->weight('bold')
                            ->state(fn (PurchaseOrder $record) => $record->total())
                            ->money(fn () => tenant('currency')?->value),
                    ]),
            ]);
    }
}
