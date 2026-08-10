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
            ->label('Open Till')
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->schema([
                Select::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::query()->where('is_active', true)->pluck('name', 'id'))
                    ->default(fn () => session('current_location_id'))
                    ->searchable()
                    ->required(),
                TextInput::make('opening_float')
                    ->label('Opening float')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->default(0)
                    ->prefix(fn () => tenant('currency')?->value)
                    ->helperText('The cash already in the drawer before any sales are rung up.'),
            ])
            ->action(function (array $data) {
                try {
                    CashSession::openAt((int) $data['location_id'], (float) $data['opening_float']);

                    Notification::make()->title('Till opened')->success()->send();
                } catch (RuntimeException $e) {
                    Notification::make()
                        ->title('Cannot open till')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function closeTill(): Action
    {
        return Action::make('closeTill')
            ->label('Close Till')
            ->icon('heroicon-o-lock-closed')
            ->color('warning')
            ->visible(fn (CashSession $record) => $record->status === CashSessionStatus::Open)
            ->schema(fn (CashSession $record) => [
                Placeholder::make('expected_cash_preview')
                    ->label('Expected cash right now')
                    ->content(fn () => ($record->location->name).': '.tenant('currency')?->value.' '.number_format($record->expectedCash(), 2)),
                TextInput::make('counted_cash')
                    ->label('Counted cash')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix(fn () => tenant('currency')?->value),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->helperText('Explain any discrepancy between counted and expected cash, if there is one.'),
            ])
            ->action(function (array $data, CashSession $record) {
                try {
                    $record->close((float) $data['counted_cash'], $data['notes'] ?? null);

                    Notification::make()->title('Till closed')->success()->send();
                } catch (RuntimeException $e) {
                    Notification::make()
                        ->title('Cannot close till')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
