<?php

namespace App\Filament\Business\Resources\SupplierTransactions;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\SupplierTransactions\Pages\CreateSupplierTransaction;
use App\Filament\Business\Resources\SupplierTransactions\Pages\EditSupplierTransaction;
use App\Filament\Business\Resources\SupplierTransactions\Pages\ListSupplierTransactions;
use App\Filament\Business\Resources\SupplierTransactions\Pages\ViewSupplierTransaction;
use App\Filament\Business\Resources\SupplierTransactions\Schemas\SupplierTransactionForm;
use App\Filament\Business\Resources\SupplierTransactions\Schemas\SupplierTransactionInfolist;
use App\Filament\Business\Resources\SupplierTransactions\Tables\SupplierTransactionsTable;
use App\Models\SupplierTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SupplierTransactionResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = SupplierTransaction::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager];
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Purchasing;

    protected static ?int $navigationSort = 23;

    public static function getModelLabel(): string
    {
        return __('supplier_transactions.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('supplier_transactions.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return SupplierTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupplierTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierTransactionsTable::configure($table);
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
            'index' => ListSupplierTransactions::route('/'),
            'create' => CreateSupplierTransaction::route('/create'),
            'view' => ViewSupplierTransaction::route('/{record}'),
            'edit' => EditSupplierTransaction::route('/{record}/edit'),
        ];
    }
}
