<?php

namespace App\Filament\Business\Resources\StockAdjustments\Schemas;

use App\Models\StockAdjustment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockAdjustmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Adjustment details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('product.name')
                            ->label('Product'),
                        TextEntry::make('location.name')
                            ->label('Location'),
                        TextEntry::make('batch.batch_number')
                            ->label('Batch')
                            ->placeholder('No batch #'),
                        TextEntry::make('quantity_change')
                            ->label('Quantity')
                            ->color(fn (StockAdjustment $record) => $record->quantity_change >= 0 ? 'success' : 'danger')
                            ->formatStateUsing(fn (StockAdjustment $record) => ($record->quantity_change >= 0 ? '+' : '').rtrim(rtrim(number_format($record->quantity_change, 2), '0'), '.')),
                        TextEntry::make('reason')
                            ->badge(),
                        TextEntry::make('adjustment_date')
                            ->date(),
                        TextEntry::make('notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
