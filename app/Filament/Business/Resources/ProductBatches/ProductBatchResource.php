<?php

namespace App\Filament\Business\Resources\ProductBatches;

use App\Filament\Business\Resources\ProductBatches\Pages\CreateProductBatch;
use App\Filament\Business\Resources\ProductBatches\Pages\EditProductBatch;
use App\Filament\Business\Resources\ProductBatches\Pages\ListProductBatches;
use App\Filament\Business\Resources\ProductBatches\Pages\ViewProductBatch;
use App\Filament\Business\Resources\ProductBatches\Schemas\ProductBatchForm;
use App\Filament\Business\Resources\ProductBatches\Schemas\ProductBatchInfolist;
use App\Filament\Business\Resources\ProductBatches\Tables\ProductBatchesTable;
use App\Models\ProductBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductBatchResource extends Resource
{
    protected static ?string $model = ProductBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProductBatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductBatchesTable::configure($table);
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
            'index' => ListProductBatches::route('/'),
            'create' => CreateProductBatch::route('/create'),
            'view' => ViewProductBatch::route('/{record}'),
            'edit' => EditProductBatch::route('/{record}/edit'),
        ];
    }
}
