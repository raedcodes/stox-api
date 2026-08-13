<?php

namespace App\Filament\Business\Resources\StockTransfers\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockTransferInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('stock_transfers.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('transfer_number')
                            ->label(__('stock_transfers.columns.transfer_number')),
                        TextEntry::make('status')
                            ->label(__('common.fields.status'))
                            ->badge(),
                        TextEntry::make('transfer_date')
                            ->label(__('common.fields.date'))
                            ->date(),
                        TextEntry::make('fromLocation.name')
                            ->label(__('stock_transfers.fields.from_location')),
                        TextEntry::make('toLocation.name')
                            ->label(__('stock_transfers.fields.to_location')),
                        TextEntry::make('notes')
                            ->label(__('common.fields.notes'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('stock_transfers.items_section_title'))
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('common.fields.product')),
                                TableColumn::make(__('common.fields.quantity')),
                            ])
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label(__('common.fields.product')),
                                TextEntry::make('quantity')
                                    ->label(__('common.fields.quantity')),
                            ]),
                    ]),
            ]);
    }
}
