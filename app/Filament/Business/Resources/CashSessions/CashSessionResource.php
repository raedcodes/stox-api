<?php

namespace App\Filament\Business\Resources\CashSessions;

use App\Enums\CashSessionStatus;
use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\CashSessions\Actions\CashSessionWorkflowActions;
use App\Filament\Business\Resources\CashSessions\Pages\ManageCashSessions;
use App\Models\CashSession;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CashSessionResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = CashSession::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager, UserRole::Cashier];
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Finance;

    protected static ?int $navigationSort = 61;

    protected static ?string $navigationLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('cash_sessions.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('cash_sessions.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('cash_sessions.plural_model_label');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('location'))
            ->columns([
                TextColumn::make('location.name')
                    ->label(__('common.fields.location')),
                TextColumn::make('status')
                    ->label(__('common.fields.status'))
                    ->badge(),
                TextColumn::make('opened_at')
                    ->label(__('cash_sessions.fields.opened'))
                    ->dateTime(),
                TextColumn::make('opener.name')
                    ->label(__('cash_sessions.fields.opened_by'))
                    ->placeholder('—'),
                TextColumn::make('opening_float')
                    ->label(__('cash_sessions.fields.opening_float'))
                    ->money(fn () => tenant('currency')?->value),
                TextColumn::make('closed_at')
                    ->label(__('cash_sessions.fields.closed'))
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('expected_cash')
                    ->label(__('cash_sessions.fields.expected'))
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder('—'),
                TextColumn::make('counted_cash')
                    ->label(__('cash_sessions.fields.counted'))
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder('—'),
                TextColumn::make('variance')
                    ->label(__('cash_sessions.fields.variance'))
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder('—')
                    ->color(fn ($state) => $state === null ? null : ((float) $state === 0.0 ? 'success' : 'danger')),
            ])
            ->defaultSort('opened_at', 'desc')
            ->filters([
                SelectFilter::make('location_id')
                    ->label(__('common.fields.location'))
                    ->options(fn () => Location::query()->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->label(__('common.fields.status'))
                    ->options(CashSessionStatus::options()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                CashSessionWorkflowActions::closeTill(),
                DeleteAction::make()
                    ->visible(fn (CashSession $record) => $record->status === CashSessionStatus::Closed),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCashSessions::route('/'),
        ];
    }
}
