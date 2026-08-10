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

    protected static ?string $navigationLabel = 'Cash Drawer';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('location'))
            ->columns([
                TextColumn::make('location.name')
                    ->label('Location'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('opened_at')
                    ->label('Opened')
                    ->dateTime(),
                TextColumn::make('opener.name')
                    ->label('Opened by')
                    ->placeholder('—'),
                TextColumn::make('opening_float')
                    ->label('Opening float')
                    ->money(fn () => tenant('currency')?->value),
                TextColumn::make('closed_at')
                    ->label('Closed')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('expected_cash')
                    ->label('Expected')
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder('—'),
                TextColumn::make('counted_cash')
                    ->label('Counted')
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder('—'),
                TextColumn::make('variance')
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder('—')
                    ->color(fn ($state) => $state === null ? null : ((float) $state === 0.0 ? 'success' : 'danger')),
            ])
            ->defaultSort('opened_at', 'desc')
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::query()->pluck('name', 'id')),
                SelectFilter::make('status')
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
