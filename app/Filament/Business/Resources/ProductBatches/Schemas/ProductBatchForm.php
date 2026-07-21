<?php

namespace App\Filament\Business\Resources\ProductBatches\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('batch_number')
                    ->label('Batch / lot number')
                    ->required(),
                TextInput::make('quantity')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                DatePicker::make('expiry_date')
                    ->helperText('Leave empty if this batch doesn\'t expire.'),
                Checkbox::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Turn off for a batch that\'s been written off, recalled, or fully used.'),
            ]);
    }
}
