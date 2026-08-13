<?php

namespace App\Filament\Business\Resources\Suppliers;

use App\Enums\NavigationGroup;
use App\Enums\PaymentTerms;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SupplierTransactionType;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\Suppliers\Pages\ManageSuppliers;
use App\Models\Supplier;
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

class SupplierResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = Supplier::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper];
    }

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Purchasing;

    protected static ?int $navigationSort = 22;

    public static function getModelLabel(): string
    {
        return __('suppliers.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('suppliers.plural_model_label');
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
                TextInput::make('contact_person')
                    ->label(__('suppliers.fields.contact_person')),
                TextInput::make('email')
                    ->label(__('common.fields.email'))
                    ->email(),
                TextInput::make('phone')
                    ->label(__('common.fields.phone'))
                    ->tel(),
                Textarea::make('address')
                    ->label(__('common.fields.address'))
                    ->rows(2),
                Select::make('payment_terms')
                    ->label(__('suppliers.fields.payment_terms'))
                    ->options(PaymentTerms::options()),
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
                ->withSum(['transactions as charges_sum' => fn (Builder $query) => $query->where('type', SupplierTransactionType::Charge)], 'amount')
                ->withSum(['transactions as reducing_sum' => fn (Builder $query) => $query->whereIn('type', array_map(fn (SupplierTransactionType $type) => $type->value, SupplierTransactionType::reducingTypes()))], 'amount')
                ->withCount(['purchaseOrders as open_purchase_orders_count' => fn (Builder $query) => $query->whereIn('status', [PurchaseOrderStatus::Ordered, PurchaseOrderStatus::PartiallyReceived])]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('common.fields.name'))
                    ->searchable(),
                TextColumn::make('contact_person')
                    ->label(__('suppliers.fields.contact_person'))
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label(__('common.fields.email'))
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->label(__('common.fields.phone'))
                    ->placeholder('—'),
                TextColumn::make('address')
                    ->label(__('common.fields.address'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_terms')
                    ->label(__('suppliers.fields.payment_terms'))
                    ->placeholder('—'),
                TextColumn::make('open_purchase_orders_count')
                    ->label(__('suppliers.columns.open_purchase_orders'))
                    ->badge()
                    ->color(fn ($state) => (int) $state > 0 ? 'warning' : 'gray'),
                TextColumn::make('total_purchases')
                    ->label(__('suppliers.columns.total_purchases'))
                    ->state(fn (Supplier $record) => (float) ($record->charges_sum ?? 0))
                    ->money(fn () => tenant('currency')?->value)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('balance')
                    ->label(__('suppliers.columns.balance'))
                    ->state(fn (Supplier $record) => (float) ($record->charges_sum ?? 0) - (float) ($record->reducing_sum ?? 0))
                    ->money(fn () => tenant('currency')?->value)
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success'),
                IconColumn::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('common.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at_since')
                    ->label(__('common.fields.since'))
                    ->state(fn (Supplier $record) => $record->created_at)
                    ->since()
                    ->dateTooltip()
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('created_at', $direction)),
            ])
            ->filters([
                SelectFilter::make('payment_terms')
                    ->label(__('suppliers.fields.payment_terms'))
                    ->options(PaymentTerms::options()),
                TernaryFilter::make('is_active')
                    ->label(__('common.fields.is_active')),
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSuppliers::route('/'),
        ];
    }
}
