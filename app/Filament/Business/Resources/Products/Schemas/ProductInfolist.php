<?php

namespace App\Filament\Business\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Photos')
                    ->schema([
                        ImageEntry::make('images')
                            ->hiddenLabel(),
                    ])
                    ->visible(fn (Product $record) => filled($record->images)),
                Section::make('Product details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('sku')
                            ->label('SKU'),
                        TextEntry::make('barcode')
                            ->placeholder('—'),
                        TextEntry::make('category.name')
                            ->label('Category')
                            ->placeholder('—'),
                        TextEntry::make('brand.name')
                            ->label('Brand')
                            ->placeholder('—'),
                        TextEntry::make('unit.name')
                            ->label('Unit'),
                        TextEntry::make('stockOnHand')
                            ->label('Stock on hand')
                            ->state(fn (Product $record) => $record->stockOnHand()),
                        TextEntry::make('min_stock_level')
                            ->label('Minimum stock level')
                            ->placeholder('—'),
                        TextEntry::make('reorder_level')
                            ->label('Reorder level')
                            ->placeholder('—'),
                        IconEntry::make('is_active')
                            ->boolean(),
                    ]),
                Section::make('Pricing')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('cost_price')
                            ->money(fn () => tenant('currency')?->value)
                            ->placeholder('—'),
                        TextEntry::make('selling_price')
                            ->money(fn () => tenant('currency')?->value)
                            ->placeholder('—'),
                        IconEntry::make('tax_inclusive')
                            ->label('Price includes tax')
                            ->boolean(),
                    ]),
            ]);
    }
}
