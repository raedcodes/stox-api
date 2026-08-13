<?php

namespace App\Filament\Business\Resources\Customers\Actions;

use App\Enums\CustomerTransactionType;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class RecordCustomerPaymentAction
{
    /**
     * Reusable both as a table row action (Filament auto-binds the row's
     * record) and bound explicitly via ->record($customer) from a plain
     * page — see CustomerStatement's header actions.
     */
    public static function make(): Action
    {
        return Action::make('recordPayment')
            ->label(__('customers.actions.record_payment'))
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->visible(fn (?Customer $record) => $record !== null && ! $record->is_walk_in)
            ->schema(fn (Customer $record) => [
                Placeholder::make('current_balance')
                    ->label(__('customers.fields.current_balance'))
                    ->content(fn () => tenant('currency')?->value.' '.number_format($record->balance(), 2)),
                TextInput::make('amount')
                    ->label(__('common.fields.amount'))
                    ->numeric()
                    ->minValue(0.01)
                    ->required()
                    ->prefix(fn () => tenant('currency')?->value),
                DatePicker::make('transaction_date')
                    ->label(__('common.fields.date'))
                    ->required()
                    ->default(now()),
                TextInput::make('description')
                    ->label(__('common.fields.reference'))
                    ->helperText(__('customers.reference_helper')),
            ])
            ->action(function (array $data, Customer $record) {
                $balance = $record->balance();

                $record->transactions()->create([
                    'type' => CustomerTransactionType::Payment,
                    'amount' => $data['amount'],
                    'description' => $data['description'] ?? null,
                    'transaction_date' => $data['transaction_date'],
                ]);

                if ((float) $data['amount'] > $balance) {
                    Notification::make()
                        ->title(__('customers.notifications.payment_recorded'))
                        ->body(__('customers.notifications.exceeds_balance'))
                        ->warning()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('customers.notifications.payment_recorded'))
                    ->success()
                    ->send();
            });
    }
}
