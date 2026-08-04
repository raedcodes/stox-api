<?php

namespace App\Filament\Business\Resources\Customers;

use App\Enums\CustomerType;
use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\Customers\Pages\ManageCustomers;
use App\Models\Customer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CustomerResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = Customer::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager];
    }

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Sales;

    protected static ?int $navigationSort = 12;

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone', 'email'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Phone' => $record->phone,
            'Email' => $record->email,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('customer_type')
                    ->label('Customer type')
                    ->options(CustomerType::options())
                    ->default(CustomerType::B2C)
                    ->required(),
                TextInput::make('email')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                Textarea::make('address')
                    ->rows(2),
                Checkbox::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->formatStateUsing(fn (Customer $record) => $record->is_walk_in ? "{$record->name} (default)" : $record->name),
                TextColumn::make('customer_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('email')
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->placeholder('—'),
                TextColumn::make('balance')
                    ->state(fn (Customer $record) => $record->balance())
                    ->money(fn () => tenant('currency')?->value)
                    ->color(fn (Customer $record) => $record->balance() > 0 ? 'danger' : 'success'),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Created at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at_since')
                    ->label('Since')
                    ->state(fn (Customer $record) => $record->created_at)
                    ->since()
                    ->dateTooltip()
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('created_at', $direction)),
            ])
            ->filters([
                SelectFilter::make('customer_type')
                    ->label('Type')
                    ->options(CustomerType::options()),
                TernaryFilter::make('is_active'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Customer $record) => ! $record->is_walk_in),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->fetchSelectedRecords(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCustomers::route('/'),
        ];
    }
}
