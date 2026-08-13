<?php

namespace App\Filament\Business\Resources\Expenses;

use App\Enums\ExpenseCategory;
use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\Expenses\Pages\ManageExpenses;
use App\Models\Expense;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ExpenseResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = Expense::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager];
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Finance;

    protected static ?int $navigationSort = 60;

    public static function getModelLabel(): string
    {
        return __('expenses.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('expenses.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category')
                    ->label(__('expenses.fields.category'))
                    ->options(ExpenseCategory::options())
                    ->default(ExpenseCategory::Other->value)
                    ->required(),
                TextInput::make('description')
                    ->label(__('common.fields.description')),
                TextInput::make('amount')
                    ->label(__('common.fields.amount'))
                    ->numeric()
                    ->minValue(0.01)
                    ->required()
                    ->prefix(fn () => tenant('currency')?->value),
                DatePicker::make('expense_date')
                    ->label(__('common.fields.date'))
                    ->required()
                    ->default(now()),
                Select::make('location_id')
                    ->label(__('common.fields.location'))
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => session('current_location_id'))
                    ->helperText(__('expenses.fields.location_helper')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('location'))
            ->columns([
                TextColumn::make('expense_date')
                    ->label(__('common.fields.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('category')
                    ->label(__('expenses.fields.category'))
                    ->badge(),
                TextColumn::make('description')
                    ->label(__('common.fields.description'))
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('location.name')
                    ->label(__('common.fields.location'))
                    ->placeholder(__('expenses.company_wide')),
                TextColumn::make('amount')
                    ->label(__('common.fields.amount'))
                    ->money(fn () => tenant('currency')?->value)
                    ->sortable(),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->label(__('expenses.fields.category'))
                    ->options(ExpenseCategory::options()),
                SelectFilter::make('location_id')
                    ->label(__('common.fields.location'))
                    ->options(fn () => Location::query()->pluck('name', 'id')),
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
            'index' => ManageExpenses::route('/'),
        ];
    }
}
