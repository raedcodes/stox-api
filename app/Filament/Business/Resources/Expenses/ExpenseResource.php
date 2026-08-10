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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category')
                    ->options(ExpenseCategory::options())
                    ->default(ExpenseCategory::Other->value)
                    ->required(),
                TextInput::make('description'),
                TextInput::make('amount')
                    ->numeric()
                    ->minValue(0.01)
                    ->required()
                    ->prefix(fn () => tenant('currency')?->value),
                DatePicker::make('expense_date')
                    ->label('Date')
                    ->required()
                    ->default(now()),
                Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => session('current_location_id'))
                    ->helperText('Leave blank for a company-wide expense not tied to one location.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('location'))
            ->columns([
                TextColumn::make('expense_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge(),
                TextColumn::make('description')
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->placeholder('Company-wide'),
                TextColumn::make('amount')
                    ->money(fn () => tenant('currency')?->value)
                    ->sortable(),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->options(ExpenseCategory::options()),
                SelectFilter::make('location_id')
                    ->label('Location')
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
