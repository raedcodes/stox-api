<?php

namespace App\Filament\Business\Resources\ProductBatches;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
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
use UnitEnum;

class ProductBatchResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = ProductBatch::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper];
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Catalog;

    protected static ?int $navigationSort = 33;

    public static function getModelLabel(): string
    {
        return __('product_batches.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('product_batches.plural_model_label');
    }

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
