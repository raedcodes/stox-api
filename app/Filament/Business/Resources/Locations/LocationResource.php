<?php

namespace App\Filament\Business\Resources\Locations;

use App\Enums\LocationType;
use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\Locations\Pages\ManageLocations;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LocationResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = Location::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper];
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Inventory;

    protected static ?int $navigationSort = 45;

    public static function getModelLabel(): string
    {
        return __('locations.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('locations.plural_model_label');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('common.fields.name'))
                    ->required(),
                Select::make('type')
                    ->label(__('common.fields.type'))
                    ->options(LocationType::options())
                    ->required(),
                Checkbox::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('common.fields.name'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('common.fields.type')),
                IconColumn::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('common.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at_since')
                    ->label(__('common.fields.since'))
                    ->state(fn (Location $record) => $record->created_at)
                    ->since()
                    ->dateTooltip()
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('created_at', $direction)),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateHeading(__('locations.empty_state.heading'))
            ->emptyStateDescription(__('locations.empty_state.description'))
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLocations::route('/'),
        ];
    }
}
