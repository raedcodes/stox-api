<?php

namespace App\Filament\Business\Resources\StockAdjustments;

use App\Filament\Business\Resources\StockAdjustments\Pages\CreateStockAdjustment;
use App\Filament\Business\Resources\StockAdjustments\Pages\ListStockAdjustments;
use App\Filament\Business\Resources\StockAdjustments\Pages\ViewStockAdjustment;
use App\Filament\Business\Resources\StockAdjustments\Schemas\StockAdjustmentForm;
use App\Filament\Business\Resources\StockAdjustments\Schemas\StockAdjustmentInfolist;
use App\Filament\Business\Resources\StockAdjustments\Tables\StockAdjustmentsTable;
use App\Models\StockAdjustment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return StockAdjustmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockAdjustmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockAdjustmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockAdjustments::route('/'),
            'create' => CreateStockAdjustment::route('/create'),
            'view' => ViewStockAdjustment::route('/{record}'),
        ];
    }
}
