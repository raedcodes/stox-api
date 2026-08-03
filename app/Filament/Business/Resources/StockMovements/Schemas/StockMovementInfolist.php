<?php

namespace App\Filament\Business\Resources\StockMovements\Schemas;

use App\Models\StockMovement;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockMovementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Movement details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime(),
                        TextEntry::make('product.name')
                            ->label('Product'),
                        TextEntry::make('location.name')
                            ->label('Location'),
                        TextEntry::make('batch.batch_number')
                            ->label('Batch')
                            ->placeholder('No batch #'),
                        TextEntry::make('type')
                            ->badge(),
                        TextEntry::make('quantity_change')
                            ->label('Quantity')
                            ->color(fn (StockMovement $record) => $record->quantity_change >= 0 ? 'success' : 'danger')
                            ->formatStateUsing(fn (StockMovement $record) => ($record->quantity_change >= 0 ? '+' : '').rtrim(rtrim(number_format($record->quantity_change, 2), '0'), '.')),
                        TextEntry::make('quantity_after')
                            ->label('On hand after'),
                        TextEntry::make('referenceLabel')
                            ->label('Reference')
                            ->state(fn (StockMovement $record) => $record->referenceLabel())
                            ->placeholder('—'),
                        TextEntry::make('performedBy.name')
                            ->label('By')
                            ->placeholder('System'),
                        TextEntry::make('reason')
                            ->placeholder('—'),
                        TextEntry::make('notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
