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
                Section::make('Transfer details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('transfer_number')
                            ->label('Transfer #'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('transfer_date')
                            ->date(),
                        TextEntry::make('fromLocation.name')
                            ->label('From location'),
                        TextEntry::make('toLocation.name')
                            ->label('To location'),
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
                                TableColumn::make('Quantity'),
                            ])
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Product'),
                                TextEntry::make('quantity')
                                    ->label('Quantity'),
                            ]),
                    ]),
            ]);
    }
}
