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
                Section::make('Return details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('purchaseOrder.po_number')
                            ->label('PO #'),
                        TextEntry::make('purchaseOrder.supplier.name')
                            ->label('Supplier'),
                        TextEntry::make('return_date')
                            ->date(),
                        IconEntry::make('processed_at')
                            ->label('Processed')
                            ->boolean()
                            ->state(fn (PurchaseReturn $record) => $record->processed_at !== null),
                        TextEntry::make('reason')
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
                                TableColumn::make('Qty'),
                                TableColumn::make('Unit cost'),
                                TableColumn::make('Line total'),
                            ])
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Product'),
                                TextEntry::make('quantity')
                                    ->label('Qty'),
                                TextEntry::make('unit_cost')
                                    ->label('Unit cost')
                                    ->money(fn () => tenant('currency')?->value),
                                TextEntry::make('lineTotal')
                                    ->label('Line total')
                                    ->state(fn (PurchaseReturnItem $record) => $record->quantity * $record->unit_cost)
                                    ->money(fn () => tenant('currency')?->value),
                            ]),
                    ]),
                Section::make('Total')
                    ->schema([
                        TextEntry::make('total')
                            ->label('Total')
                            ->weight('bold')
                            ->state(fn (PurchaseReturn $record) => $record->total())
                            ->money(fn () => tenant('currency')?->value),
                    ]),
            ]);
    }
}
