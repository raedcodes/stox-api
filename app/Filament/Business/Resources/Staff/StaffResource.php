<?php

namespace App\Filament\Business\Resources\Staff;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\Staff\Pages\CreateStaff;
use App\Filament\Business\Resources\Staff\Pages\EditStaff;
use App\Filament\Business\Resources\Staff\Pages\ListStaff;
use App\Filament\Business\Resources\Staff\Schemas\StaffForm;
use App\Filament\Business\Resources\Staff\Tables\StaffTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StaffResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Staff';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Settings;

    protected static ?int $navigationSort = 63;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner];
    }

    /**
     * Only this business's own staff (Manager/Cashier/Storekeeper) — never
     * the Owner account itself, so there's no way to accidentally edit,
     * demote, or delete your own account from this screen.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant('id'))
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', [
                UserRole::Manager->value,
                UserRole::Cashier->value,
                UserRole::StoreKeeper->value,
            ]));
    }

    public static function form(Schema $schema): Schema
    {
        return StaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffTable::configure($table);
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
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }
}
