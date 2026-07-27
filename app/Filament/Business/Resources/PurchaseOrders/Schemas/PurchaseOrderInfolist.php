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
                Section::make('Purchase order details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('po_number')
                            ->label('PO #'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('supplier.name')
                            ->label('Supplier'),
                        TextEntry::make('location.name')
                            ->label('Deliver to'),
                        TextEntry::make('order_date')
                            ->date(),
                        TextEntry::make('expected_date')
                            ->label('Expected delivery date')
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Items')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Product'),
                                TableColumn::make('Ordered'),
                                TableColumn::make('Received'),
                                TableColumn::make('Unit cost'),
                                TableColumn::make('Line total'),
                            ])
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Product'),
                                TextEntry::make('quantity_ordered')
                                    ->label('Ordered'),
                                TextEntry::make('quantity_received')
                                    ->label('Received'),
                                TextEntry::make('unit_cost')
                                    ->label('Unit cost')
                                    ->money(fn () => tenant('currency')?->value),
                                TextEntry::make('lineTotal')
                                    ->label('Line total')
                                    ->state(fn (PurchaseOrderItem $record) => $record->quantity_ordered * $record->unit_cost)
                                    ->money(fn () => tenant('currency')?->value),
                            ]),
                    ]),
                Section::make('Landed costs')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('charges')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Description'),
                                TableColumn::make('Amount'),
                            ])
                            ->schema([
                                TextEntry::make('description')
                                    ->label('Description'),
                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->money(fn () => tenant('currency')?->value),
                            ]),
                    ])
                    ->visible(fn (PurchaseOrder $record) => $record->charges()->exists()),
                Section::make('Totals')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('subtotal')
                            ->state(fn (PurchaseOrder $record) => $record->subtotal())
                            ->money(fn () => tenant('currency')?->value),
                        TextEntry::make('landedCostTotal')
                            ->label('Landed costs')
                            ->state(fn (PurchaseOrder $record) => $record->landedCostTotal())
                            ->money(fn () => tenant('currency')?->value),
                        TextEntry::make('total')
                            ->label('Total')
                            ->weight('bold')
                            ->state(fn (PurchaseOrder $record) => $record->total())
                            ->money(fn () => tenant('currency')?->value),
                    ]),
            ]);
    }
}
