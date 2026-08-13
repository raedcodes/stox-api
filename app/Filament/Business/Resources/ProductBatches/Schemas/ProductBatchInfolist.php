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
                Section::make(__('product_batches.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('product.name')
                            ->label(__('common.fields.product')),
                        TextEntry::make('location.name')
                            ->label(__('common.fields.location')),
                        TextEntry::make('batch_number')
                            ->label(__('product_batches.fields.batch_number'))
                            ->placeholder('—'),
                        TextEntry::make('quantity')
                            ->label(__('common.fields.quantity')),
                        TextEntry::make('unit_cost')
                            ->label(__('product_batches.fields.unit_cost'))
                            ->money(fn () => tenant('currency')?->value)
                            ->placeholder('—'),
                        TextEntry::make('manufactured_date')
                            ->label(__('product_batches.fields.manufactured_date'))
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('expiry_date')
                            ->label(__('product_batches.fields.expiry_date'))
                            ->date()
                            ->placeholder(__('product_batches.no_expiry'))
                            ->color(fn (?ProductBatch $record) => match (true) {
                                $record?->expiry_date === null => 'gray',
                                $record->expiry_date->isPast() => 'danger',
                                now()->diffInDays($record->expiry_date) <= 30 => 'warning',
                                default => 'success',
                            }),
                        IconEntry::make('is_active')
                            ->label(__('common.fields.is_active'))
                            ->boolean(),
                    ]),
            ]);
    }
}
