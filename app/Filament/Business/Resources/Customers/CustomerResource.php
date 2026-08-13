<?php

namespace App\Filament\Business\Resources\Customers;

use App\Enums\CustomerTransactionType;
use App\Enums\CustomerType;
use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\Customers\Actions\RecordCustomerPaymentAction;
use App\Filament\Business\Resources\Customers\Pages\ManageCustomers;
use App\Models\Customer;
use BackedEnum;
use Filament\Actions\ActionGroup;
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

    public static function getModelLabel(): string
    {
        return __('customers.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('customers.plural_model_label');
    }

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
            __('common.fields.phone') => $record->phone,
            __('common.fields.email') => $record->email,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('common.fields.name'))
                    ->required(),
                Select::make('customer_type')
                    ->label(__('customers.fields.customer_type'))
                    ->options(CustomerType::options())
                    ->default(CustomerType::B2C)
                    ->required(),
                TextInput::make('credit_limit')
                    ->label(__('customers.fields.credit_limit'))
                    ->numeric()
                    ->minValue(0)
                    ->prefix(fn () => tenant('currency')?->value)
                    ->helperText(__('customers.fields.credit_limit_helper')),
                TextInput::make('email')
                    ->label(__('common.fields.email'))
                    ->email(),
                TextInput::make('phone')
                    ->label(__('common.fields.phone'))
                    ->tel(),
                Textarea::make('address')
                    ->label(__('common.fields.address'))
                    ->rows(2),
                Checkbox::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                // Two correlated-subquery sums instead of calling ->balance()
                // per row (which runs its own grouped aggregate query every
                // time it's called — twice per row here, once for the state
                // and once for the color).
                ->withSum(['transactions as credit_sales_sum' => fn (Builder $query) => $query->where('type', CustomerTransactionType::CreditSale)], 'amount')
                ->withSum(['transactions as reducing_sum' => fn (Builder $query) => $query->whereIn('type', array_map(fn (CustomerTransactionType $type) => $type->value, CustomerTransactionType::reducingTypes()))], 'amount'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('common.fields.name'))
                    ->searchable()
                    ->formatStateUsing(fn (Customer $record) => $record->is_walk_in ? __('customers.default_suffix', ['name' => $record->name]) : $record->name),
                TextColumn::make('customer_type')
                    ->label(__('common.fields.type'))
                    ->badge(),
                TextColumn::make('email')
                    ->label(__('common.fields.email'))
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->label(__('common.fields.phone'))
                    ->placeholder('—'),
                TextColumn::make('balance')
                    ->label(__('customers.columns.balance'))
                    ->state(fn (Customer $record) => (float) ($record->credit_sales_sum ?? 0) - (float) ($record->reducing_sum ?? 0))
                    ->money(fn () => tenant('currency')?->value)
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success'),
                TextColumn::make('credit_limit')
                    ->label(__('customers.fields.credit_limit'))
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder(__('customers.no_limit'))
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('common.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at_since')
                    ->label(__('common.fields.since'))
                    ->state(fn (Customer $record) => $record->created_at)
                    ->since()
                    ->dateTooltip()
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('created_at', $direction)),
            ])
            ->filters([
                SelectFilter::make('customer_type')
                    ->label(__('common.fields.type'))
                    ->options(CustomerType::options()),
                TernaryFilter::make('is_active')
                    ->label(__('common.fields.is_active')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                RecordCustomerPaymentAction::make(),
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->visible(fn (Customer $record) => ! $record->is_walk_in),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ]),
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
