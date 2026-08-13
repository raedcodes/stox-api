<?php

namespace App\Filament\Business\Resources\Products;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\Products\Pages\CreateProduct;
use App\Filament\Business\Resources\Products\Pages\EditProduct;
use App\Filament\Business\Resources\Products\Pages\ListProducts;
use App\Filament\Business\Resources\Products\Pages\ViewProduct;
use App\Filament\Business\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Filament\Business\Resources\Products\Schemas\ProductForm;
use App\Filament\Business\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Business\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ProductResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = Product::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper];
    }

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Catalog;

    protected static ?int $navigationSort = 32;

    public static function getModelLabel(): string
    {
        return __('products.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('products.plural_model_label');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku', 'barcode'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('common.fields.sku') => $record->sku,
            __('common.fields.category') => $record->category?->name,
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['category']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
