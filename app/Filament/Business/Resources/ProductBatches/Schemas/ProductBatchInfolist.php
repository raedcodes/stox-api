<?php

namespace App\Filament\Business\Resources\ProductBatches\Schemas;

use App\Models\ProductBatch;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Batch details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('product.name')
                            ->label('Product'),
                        TextEntry::make('location.name')
                            ->label('Location'),
                        TextEntry::make('batch_number')
                            ->label('Batch / lot number')
                            ->placeholder('—'),
                        TextEntry::make('quantity'),
                        TextEntry::make('unit_cost')
                            ->label('Unit cost')
                            ->money(fn () => tenant('currency')?->value)
                            ->placeholder('—'),
                        TextEntry::make('manufactured_date')
                            ->label('Manufactured date')
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('expiry_date')
                            ->date()
                            ->placeholder('No expiry')
                            ->color(fn (?ProductBatch $record) => match (true) {
                                $record?->expiry_date === null => 'gray',
                                $record->expiry_date->isPast() => 'danger',
                                now()->diffInDays($record->expiry_date) <= 30 => 'warning',
                                default => 'success',
                            }),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ]),
            ]);
    }
}
