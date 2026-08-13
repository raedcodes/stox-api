<?php

namespace App\Filament\Business\Resources\StockAdjustments;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
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
use UnitEnum;

class StockAdjustmentResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = StockAdjustment::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper];
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Inventory;

    protected static ?int $navigationSort = 41;

    public static function getModelLabel(): string
    {
        return __('stock_adjustments.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('stock_adjustments.plural_model_label');
    }

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
