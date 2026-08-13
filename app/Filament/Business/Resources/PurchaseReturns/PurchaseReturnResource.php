<?php

namespace App\Filament\Business\Resources\PurchaseReturns;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\PurchaseReturns\Pages\CreatePurchaseReturn;
use App\Filament\Business\Resources\PurchaseReturns\Pages\EditPurchaseReturn;
use App\Filament\Business\Resources\PurchaseReturns\Pages\ListPurchaseReturns;
use App\Filament\Business\Resources\PurchaseReturns\Pages\ViewPurchaseReturn;
use App\Filament\Business\Resources\PurchaseReturns\Schemas\PurchaseReturnForm;
use App\Filament\Business\Resources\PurchaseReturns\Schemas\PurchaseReturnInfolist;
use App\Filament\Business\Resources\PurchaseReturns\Tables\PurchaseReturnsTable;
use App\Models\PurchaseReturn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PurchaseReturnResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = PurchaseReturn::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper];
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Purchasing;

    protected static ?int $navigationSort = 21;

    public static function getModelLabel(): string
    {
        return __('purchase_returns.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('purchase_returns.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseReturnForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseReturnInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseReturnsTable::configure($table);
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
            'index' => ListPurchaseReturns::route('/'),
            'create' => CreatePurchaseReturn::route('/create'),
            'view' => ViewPurchaseReturn::route('/{record}'),
            'edit' => EditPurchaseReturn::route('/{record}/edit'),
        ];
    }
}
