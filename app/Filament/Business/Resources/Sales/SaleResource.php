<?php

namespace App\Filament\Business\Resources\Sales;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\Sales\Pages\CreateSale;
use App\Filament\Business\Resources\Sales\Pages\EditSale;
use App\Filament\Business\Resources\Sales\Pages\ListSales;
use App\Filament\Business\Resources\Sales\Pages\ViewSale;
use App\Filament\Business\Resources\Sales\Schemas\SaleForm;
use App\Filament\Business\Resources\Sales\Schemas\SaleInfolist;
use App\Filament\Business\Resources\Sales\Tables\SalesTable;
use App\Models\Sale;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SaleResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = Sale::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager];
    }

    protected static ?string $recordTitleAttribute = 'sale_number';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Sales;

    protected static ?int $navigationSort = 10;

    public static function getGloballySearchableAttributes(): array
    {
        return ['sale_number', 'customer.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Customer' => $record->customer?->name,
            'Status' => $record->status->getLabel(),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['customer']);
    }

    public static function form(Schema $schema): Schema
    {
        return SaleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SaleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesTable::configure($table);
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
            'index' => ListSales::route('/'),
            'create' => CreateSale::route('/create'),
            'view' => ViewSale::route('/{record}'),
            'edit' => EditSale::route('/{record}/edit'),
        ];
    }
}
