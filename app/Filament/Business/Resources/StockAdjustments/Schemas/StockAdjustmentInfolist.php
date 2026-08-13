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
                Section::make(__('stock_adjustments.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('product.name')
                            ->label(__('common.fields.product')),
                        TextEntry::make('location.name')
                            ->label(__('common.fields.location')),
                        TextEntry::make('batch.batch_number')
                            ->label(__('common.fields.batch'))
                            ->placeholder(__('common.fields.no_batch_number')),
                        TextEntry::make('quantity_change')
                            ->label(__('common.fields.quantity'))
                            ->color(fn (StockAdjustment $record) => $record->quantity_change >= 0 ? 'success' : 'danger')
                            ->formatStateUsing(fn (StockAdjustment $record) => ($record->quantity_change >= 0 ? '+' : '').rtrim(rtrim(number_format($record->quantity_change, 2), '0'), '.')),
                        TextEntry::make('reason')
                            ->label(__('common.fields.reason'))
                            ->badge(),
                        TextEntry::make('adjustment_date')
                            ->label(__('common.fields.date'))
                            ->date(),
                        TextEntry::make('notes')
                            ->label(__('common.fields.notes'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
