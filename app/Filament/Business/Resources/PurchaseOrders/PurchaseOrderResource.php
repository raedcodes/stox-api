<?php

namespace App\Filament\Business\Resources\PurchaseOrders;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Business\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Business\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Business\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Business\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Filament\Business\Resources\PurchaseOrders\Schemas\PurchaseOrderInfolist;
use App\Filament\Business\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use App\Models\PurchaseOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PurchaseOrderResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = PurchaseOrder::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper];
    }

    protected static ?string $recordTitleAttribute = 'po_number';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocument;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Purchasing;

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return __('purchase_orders.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('purchase_orders.plural_model_label');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['po_number', 'supplier.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('common.fields.supplier') => $record->supplier?->name,
            __('common.fields.status') => $record->status->getLabel(),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['supplier']);
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseOrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseOrdersTable::configure($table);
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
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'view' => ViewPurchaseOrder::route('/{record}'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
