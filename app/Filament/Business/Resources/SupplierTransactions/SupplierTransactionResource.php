<?php

namespace App\Filament\Business\Resources\SupplierTransactions;

use App\Filament\Business\Resources\SupplierTransactions\Pages\CreateSupplierTransaction;
use App\Filament\Business\Resources\SupplierTransactions\Pages\EditSupplierTransaction;
use App\Filament\Business\Resources\SupplierTransactions\Pages\ListSupplierTransactions;
use App\Filament\Business\Resources\SupplierTransactions\Pages\ViewSupplierTransaction;
use App\Filament\Business\Resources\SupplierTransactions\Schemas\SupplierTransactionForm;
use App\Filament\Business\Resources\SupplierTransactions\Tables\SupplierTransactionsTable;
use App\Models\SupplierTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupplierTransactionResource extends Resource
{
    protected static ?string $model = SupplierTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SupplierTransactionForm::configure($schema);
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
