<?php

namespace App\Filament\Business\Resources\PurchaseOrders\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use RuntimeException;

/**
 * The Draft -> PendingApproval -> Approved(=Ordered) -> Received chain's
 * approval-gate actions — mirrors StockTransferWorkflowActions' shape and
 * reasoning, so both workflows read the same way in the UI. Receiving
 * itself is unchanged (ReceiveStockAction already only shows once Ordered).
 */
class PurchaseOrderWorkflowActions
{
    public static function submitForApproval(): Action
    {
        return Action::make('submitForApproval')
            ->label('Submit for Approval')
            ->icon('heroicon-o-paper-airplane')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::Draft)
            ->action(fn (PurchaseOrder $record) => self::run($record, fn () => $record->submitForApproval(), 'Submitted for approval', 'Cannot submit'));
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('This places the order with the supplier.')
            ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::PendingApproval)
            ->action(fn (PurchaseOrder $record) => self::run($record, fn () => $record->approve(), 'Order approved and placed', 'Cannot approve'));
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
            ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::PendingApproval)
            ->action(fn (array $data, PurchaseOrder $record) => self::run($record, fn () => $record->reject($data['reason'] ?? null), 'Order rejected', 'Cannot reject'));
    }

    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label('Cancel Order')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (PurchaseOrder $record) => in_array($record->status, [
                PurchaseOrderStatus::Draft,
                PurchaseOrderStatus::PendingApproval,
                PurchaseOrderStatus::Ordered,
            ], true))
            ->action(fn (PurchaseOrder $record) => self::run($record, fn () => $record->cancel(), 'Order cancelled', 'Cannot cancel'));
    }

    protected static function run(PurchaseOrder $record, callable $transition, string $successTitle, string $failureTitle): void
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
