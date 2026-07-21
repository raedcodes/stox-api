<?php

namespace App\Filament\Business\Resources\PurchaseReturns;

use App\Filament\Business\Resources\PurchaseReturns\Pages\CreatePurchaseReturn;
use App\Filament\Business\Resources\PurchaseReturns\Pages\EditPurchaseReturn;
use App\Filament\Business\Resources\PurchaseReturns\Pages\ListPurchaseReturns;
use App\Filament\Business\Resources\PurchaseReturns\Pages\ViewPurchaseReturn;
use App\Filament\Business\Resources\PurchaseReturns\Schemas\PurchaseReturnForm;
use App\Filament\Business\Resources\PurchaseReturns\Tables\PurchaseReturnsTable;
use App\Models\PurchaseReturn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PurchaseReturnResource extends Resource
{
    protected static ?string $model = PurchaseReturn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PurchaseReturnForm::configure($schema);
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
