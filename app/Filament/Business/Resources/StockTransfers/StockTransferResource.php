<?php

namespace App\Filament\Business\Resources\StockTransfers;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\StockTransfers\Pages\CreateStockTransfer;
use App\Filament\Business\Resources\StockTransfers\Pages\EditStockTransfer;
use App\Filament\Business\Resources\StockTransfers\Pages\ListStockTransfers;
use App\Filament\Business\Resources\StockTransfers\Pages\ViewStockTransfer;
use App\Filament\Business\Resources\StockTransfers\Schemas\StockTransferForm;
use App\Filament\Business\Resources\StockTransfers\Schemas\StockTransferInfolist;
use App\Filament\Business\Resources\StockTransfers\Tables\StockTransfersTable;
use App\Models\StockTransfer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StockTransferResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = StockTransfer::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper];
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Inventory;

    protected static ?int $navigationSort = 43;

    public static function form(Schema $schema): Schema
    {
        return StockTransferForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockTransferInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockTransfersTable::configure($table);
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
            'index' => ListStockTransfers::route('/'),
            'create' => CreateStockTransfer::route('/create'),
            'view' => ViewStockTransfer::route('/{record}'),
            'edit' => EditStockTransfer::route('/{record}/edit'),
        ];
    }
}
