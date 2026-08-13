<?php

namespace App\Filament\Business\Resources\StockAdjustments\Schemas;

use App\Enums\StockAdjustmentReason;
use App\Models\ProductBatch;
use App\Models\ProductVariant;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StockAdjustmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label(__('common.fields.product'))
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        $set('product_variant_id', ProductVariant::soleActiveIdFor($state));
                        $set('product_batch_id', null);
                    })
                    ->required(),
                Select::make('product_variant_id')
                    ->label(__('common.fields.variant'))
                    ->options(fn (Get $get) => ProductVariant::activeOptionsFor($get('product_id')))
                    ->visible(fn (Get $get) => ProductVariant::hasMultipleActiveFor($get('product_id')))
                    ->required(fn (Get $get) => ProductVariant::hasMultipleActiveFor($get('product_id')))
                    ->dehydratedWhenHidden()
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('product_batch_id', null)),
                Select::make('location_id')
                    ->label(__('common.fields.location'))
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => session('current_location_id'))
                    ->live()
                    ->afterStateUpdated(fn (Get $get, $set) => $set('product_batch_id', null))
                    ->required(),
                Select::make('product_batch_id')
                    ->label(__('common.fields.batch'))
                    ->options(function (Get $get) {
                        $variantId = $get('product_variant_id') ?: ProductVariant::soleActiveIdFor($get('product_id'));

                        if (! $variantId || ! $get('location_id')) {
                            return [];
                        }

                        return ProductBatch::query()
                            ->where('product_variant_id', $variantId)
                            ->where('location_id', $get('location_id'))
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn (ProductBatch $batch) => [
                                $batch->id => ($batch->batch_number ?? __('common.fields.no_batch_number')).
                                    ' — '.__('stock_adjustments.qty_abbr').' '.rtrim(rtrim(number_format($batch->quantity, 2), '0'), '.').
                                    ($batch->expiry_date ? ' — '.__('stock_adjustments.exp_abbr').' '.$batch->expiry_date->format('Y-m-d') : ''),
                            ]);
                    })
                    ->searchable()
                    ->required()
                    ->helperText(__('stock_adjustments.batch_helper')),
                Radio::make('direction')
                    ->label(__('stock_adjustments.fields.direction'))
                    ->options([
                        'increase' => __('stock_adjustments.direction_increase'),
                        'decrease' => __('stock_adjustments.direction_decrease'),
                    ])
                    ->default('decrease')
                    ->required()
                    ->live(),
                TextInput::make('quantity')
                    ->label(__('common.fields.quantity'))
                    ->numeric()
                    ->minValue(0.01)
                    ->required(),
                Select::make('reason')
                    ->label(__('common.fields.reason'))
                    ->options(StockAdjustmentReason::options())
                    ->required(),
                DatePicker::make('adjustment_date')
                    ->label(__('common.fields.date'))
                    ->required()
                    ->default(now()),
                Textarea::make('notes')
                    ->label(__('common.fields.notes'))
                    ->rows(2),
            ]);
    }
}
