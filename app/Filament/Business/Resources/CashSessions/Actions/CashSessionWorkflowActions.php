<?php

namespace App\Filament\Business\Resources\CashSessions\Actions;

use App\Enums\CashSessionStatus;
use App\Models\CashSession;
use App\Models\Location;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use RuntimeException;

/**
 * Opening and closing a till are the only ways a CashSession's amounts ever
 * change — there's no free-form Edit, so every figure on a closed session
 * stays a trustworthy reconciliation record.
 */
class CashSessionWorkflowActions
{
    public static function openTill(): Action
    {
        return Action::make('openTill')
            ->label(__('cash_sessions.actions.open_till'))
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->schema([
                Select::make('location_id')
                    ->label(__('common.fields.location'))
                    ->options(fn () => Location::query()->where('is_active', true)->pluck('name', 'id'))
                    ->default(fn () => session('current_location_id'))
                    ->searchable()
                    ->required(),
                TextInput::make('opening_float')
                    ->label(__('cash_sessions.fields.opening_float'))
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->default(0)
                    ->prefix(fn () => tenant('currency')?->value)
                    ->helperText(__('cash_sessions.opening_float_helper')),
            ])
            ->action(function (array $data) {
                try {
                    CashSession::openAt((int) $data['location_id'], (float) $data['opening_float']);

                    Notification::make()->title(__('cash_sessions.notifications.opened'))->success()->send();
                } catch (RuntimeException $e) {
                    Notification::make()
                        ->title(__('cash_sessions.notifications.cannot_open'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function closeTill(): Action
    {
        return Action::make('closeTill')
            ->label(__('cash_sessions.actions.close_till'))
            ->icon('heroicon-o-lock-closed')
            ->color('warning')
            ->visible(fn (CashSession $record) => $record->status === CashSessionStatus::Open)
            ->schema(fn (CashSession $record) => [
                Placeholder::make('expected_cash_preview')
                    ->label(__('cash_sessions.fields.expected_cash_now'))
                    ->content(fn () => ($record->location->name).': '.tenant('currency')?->value.' '.number_format($record->expectedCash(), 2)),
                TextInput::make('counted_cash')
                    ->label(__('cash_sessions.fields.counted_cash'))
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix(fn () => tenant('currency')?->value),
                Textarea::make('notes')
                    ->label(__('common.fields.notes'))
                    ->rows(2)
                    ->helperText(__('cash_sessions.notes_helper')),
            ])
            ->action(function (array $data, CashSession $record) {
                try {
                    $record->close((float) $data['counted_cash'], $data['notes'] ?? null);

                    Notification::make()->title(__('cash_sessions.notifications.closed'))->success()->send();
                } catch (RuntimeException $e) {
                    Notification::make()
                        ->title(__('cash_sessions.notifications.cannot_close'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
