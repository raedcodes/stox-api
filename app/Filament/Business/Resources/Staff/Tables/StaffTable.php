<?php

namespace App\Filament\Business\Resources\Staff\Tables;

use App\Enums\UserRole;
use App\Filament\Business\Resources\Staff\Schemas\StaffForm;
use App\Models\Staff;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

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
                    ->state(fn (Staff $record) => $record->getRoleNames()->first())
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
                    ->options(StaffForm::assignableRoles()),
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
                        ->label(fn (Staff $record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->icon(fn (Staff $record) => $record->is_active ? Heroicon::OutlinedLockClosed : Heroicon::OutlinedLockOpen)
                        ->color(fn (Staff $record) => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->modalDescription(fn (Staff $record) => $record->is_active
                            ? "{$record->name} will be immediately blocked from logging in. Their sales, stock, and activity history stay intact."
                            : "{$record->name} will be able to log in again immediately.")
                        ->action(function (Staff $record) {
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
