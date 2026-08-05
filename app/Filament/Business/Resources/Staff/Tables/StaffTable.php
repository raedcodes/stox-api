<?php

namespace App\Filament\Business\Resources\Staff\Tables;

use App\Enums\UserRole;
use App\Filament\Business\Resources\Staff\Schemas\StaffForm;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StaffTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->state(fn (User $record) => $record->getRoleNames()->first())
                    ->formatStateUsing(fn (?string $state) => $state ? UserRole::from($state)->getLabel() : null)
                    ->color(fn (?string $state) => match ($state) {
                        UserRole::Manager->value => 'info',
                        UserRole::Cashier->value => 'success',
                        UserRole::StoreKeeper->value => 'warning',
                        default => 'gray',
                    }),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('role')
                    ->options(StaffForm::assignableRoles())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('roles', fn (Builder $query) => $query->where('name', $data['value']))
                        : $query),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('toggleActive')
                        ->label(fn (User $record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->icon(fn (User $record) => $record->is_active ? Heroicon::OutlinedLockClosed : Heroicon::OutlinedLockOpen)
                        ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->modalDescription(fn (User $record) => $record->is_active
                            ? "{$record->name} will be immediately blocked from logging in. Their sales, stock, and activity history stay intact."
                            : "{$record->name} will be able to log in again immediately.")
                        ->action(function (User $record) {
                            $record->update(['is_active' => ! $record->is_active]);

                            activity()
                                ->causedBy(auth()->user())
                                ->performedOn($record)
                                ->withProperties(['is_active' => $record->is_active])
                                ->log($record->is_active
                                    ? "Staff account reactivated: {$record->name}"
                                    : "Staff account deactivated: {$record->name}");
                        }),
                ]),
            ]);
    }
}
