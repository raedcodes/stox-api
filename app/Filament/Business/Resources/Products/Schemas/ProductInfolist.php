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
                Section::make(__('products.photos_section_title'))
                    ->schema([
                        ImageEntry::make('images')
                            ->hiddenLabel(),
                    ])
                    ->visible(fn (Product $record) => filled($record->images)),
                Section::make(__('products.details_section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('common.fields.name')),
                        TextEntry::make('sku')
                            ->label(__('common.fields.sku')),
                        TextEntry::make('barcode')
                            ->label(__('common.fields.barcode'))
                            ->placeholder('—'),
                        TextEntry::make('description')
                            ->label(__('common.fields.description'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('category.name')
                            ->label(__('common.fields.category'))
                            ->placeholder('—'),
                        TextEntry::make('brand.name')
                            ->label(__('common.fields.brand'))
                            ->placeholder('—'),
                        TextEntry::make('unit.name')
                            ->label(__('products.fields.unit')),
                        TextEntry::make('stockOnHand')
                            ->label(__('products.fields.stock_on_hand'))
                            ->state(fn (Product $record) => $record->stockOnHand()),
                        TextEntry::make('min_stock_level')
                            ->label(__('products.fields.min_stock_level'))
                            ->placeholder('—'),
                        TextEntry::make('reorder_level')
                            ->label(__('products.fields.reorder_level'))
                            ->placeholder('—'),
                        IconEntry::make('is_active')
                            ->label(__('common.fields.is_active'))
                            ->boolean(),
                    ]),
                Section::make(__('products.pricing_section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('cost_price')
                            ->label(__('products.fields.cost_price'))
                            ->money(fn () => tenant('currency')?->value)
                            ->placeholder('—'),
                        TextEntry::make('selling_price')
                            ->label(__('products.fields.selling_price'))
                            ->money(fn () => tenant('currency')?->value)
                            ->placeholder('—'),
                        TextEntry::make('wsp')
                            ->label(__('products.fields.wsp'))
                            ->money(fn () => tenant('currency')?->value)
                            ->placeholder('—'),
                        TextEntry::make('cbm')
                            ->label(__('products.fields.cbm'))
                            ->placeholder('—'),
                        IconEntry::make('tax_inclusive')
                            ->label(__('products.fields.price_includes_tax'))
                            ->boolean(),
                    ]),
            ]);
    }
}
