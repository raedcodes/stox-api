<?php

namespace App\Filament\Business\Resources\StockTransfers\Actions;

use App\Enums\StockTransferStatus;
use App\Models\StockTransfer;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use RuntimeException;

/**
 * The Draft -> PendingApproval -> Approved -> InTransit -> (Partially)
 * Received chain's actions (everything except Receive, which has its own
 * repeater-based schema in ReceiveTransferAction) — shared between the
 * resource table and the view page so both offer the same transitions.
 */
class StockTransferWorkflowActions
{
    public static function submitForApproval(): Action
    {
        return Action::make('submitForApproval')
            ->label('Submit for Approval')
            ->icon('heroicon-o-paper-airplane')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::Draft)
            ->action(fn (StockTransfer $record) => self::run($record, fn () => $record->submitForApproval(), 'Submitted for approval', 'Cannot submit'));
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::PendingApproval)
            ->action(fn (StockTransfer $record) => self::run($record, fn () => $record->approve(), 'Transfer approved', 'Cannot approve'));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->schema([
                Textarea::make('reason')
                    ->label('Reason')
                    ->rows(2),
            ])
            ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::PendingApproval)
            ->action(fn (array $data, StockTransfer $record) => self::run($record, fn () => $record->reject($data['reason'] ?? null), 'Transfer rejected', 'Cannot reject'));
    }

    public static function markInTransit(): Action
    {
        return Action::make('markInTransit')
            ->label('Mark In Transit')
            ->icon('heroicon-o-truck')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('This deducts stock from the source location now. The destination only receives it once you confirm arrival.')
            ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::Approved)
            ->action(fn (StockTransfer $record) => self::run($record, fn () => $record->markInTransit(), 'Transfer marked in transit', 'Cannot mark in transit'));
    }

    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label('Cancel Transfer')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (StockTransfer $record) => in_array($record->status, [
                StockTransferStatus::Draft,
                StockTransferStatus::PendingApproval,
                StockTransferStatus::Approved,
            ], true))
            ->action(fn (StockTransfer $record) => self::run($record, fn () => $record->cancel(), 'Transfer cancelled', 'Cannot cancel'));
    }

    protected static function run(StockTransfer $record, callable $transition, string $successTitle, string $failureTitle): void
    {
        try {
            $transition();

            Notification::make()
                ->title($successTitle)
                ->success()
                ->send();
        } catch (RuntimeException $e) {
            Notification::make()
                ->title($failureTitle)
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
