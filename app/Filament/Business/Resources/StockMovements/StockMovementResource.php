<?php

namespace App\Filament\Business\Resources\StockMovements;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Business\Resources\StockMovements\Pages\ViewStockMovement;
use App\Filament\Business\Resources\StockMovements\Schemas\StockMovementInfolist;
use App\Filament\Business\Resources\StockMovements\Tables\StockMovementsTable;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StockMovementResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = StockMovement::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper];
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Inventory;

    protected static ?int $navigationSort = 42;

    protected static ?string $navigationLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('stock_movements.plural_model_label');
    }

    public static function getModelLabel(): string
    {
        return __('stock_movements.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('stock_movements.plural_model_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockMovementInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockMovementsTable::configure($table);
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
            'index' => ListStockMovements::route('/'),
            'view' => ViewStockMovement::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
