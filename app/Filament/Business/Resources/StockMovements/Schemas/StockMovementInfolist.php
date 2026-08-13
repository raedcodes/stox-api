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
                Section::make(__('stock_movements.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('common.fields.date'))
                            ->dateTime(),
                        TextEntry::make('product.name')
                            ->label(__('common.fields.product')),
                        TextEntry::make('location.name')
                            ->label(__('common.fields.location')),
                        TextEntry::make('batch.batch_number')
                            ->label(__('common.fields.batch'))
                            ->placeholder(__('common.fields.no_batch_number')),
                        TextEntry::make('type')
                            ->label(__('common.fields.type'))
                            ->badge(),
                        TextEntry::make('quantity_change')
                            ->label(__('common.fields.quantity'))
                            ->color(fn (StockMovement $record) => $record->quantity_change >= 0 ? 'success' : 'danger')
                            ->formatStateUsing(fn (StockMovement $record) => ($record->quantity_change >= 0 ? '+' : '').rtrim(rtrim(number_format($record->quantity_change, 2), '0'), '.')),
                        TextEntry::make('quantity_after')
                            ->label(__('stock_movements.fields.quantity_after')),
                        TextEntry::make('referenceLabel')
                            ->label(__('common.fields.reference'))
                            ->state(fn (StockMovement $record) => $record->referenceLabel())
                            ->placeholder('—'),
                        TextEntry::make('performedBy.name')
                            ->label(__('common.fields.by'))
                            ->placeholder(__('common.fields.system')),
                        TextEntry::make('reason')
                            ->label(__('common.fields.reason'))
                            ->placeholder('—'),
                        TextEntry::make('notes')
                            ->label(__('common.fields.notes'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
