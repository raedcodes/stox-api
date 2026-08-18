<?php

namespace App\Filament\Business\Resources\StockTransfers\Actions;

use App\Enums\StockTransferStatus;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\HasApprovalWorkflowActions;
use App\Models\StockTransfer;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;

/**
 * The Draft -> PendingApproval -> Approved -> InTransit -> (Partially)
 * Received chain's actions (everything except Receive, which has its own
 * repeater-based schema in ReceiveTransferAction) — shared between the
 * resource table and the view page so both offer the same transitions.
 */
class StockTransferWorkflowActions
{
    use HasApprovalWorkflowActions;

    public static function submitForApproval(): Action
    {
        return Action::make('submitForApproval')
            ->label(__('stock_transfers.actions.submit_for_approval'))
            ->icon('heroicon-o-paper-airplane')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::Draft)
            ->action(fn (StockTransfer $record) => self::run($record, fn () => $record->submitForApproval(), __('stock_transfers.notifications.submitted'), __('stock_transfers.notifications.cannot_submit')));
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label(__('stock_transfers.actions.approve'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::PendingApproval
                && auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]))
            ->action(fn (StockTransfer $record) => self::run($record, fn () => $record->approve(), __('stock_transfers.notifications.approved'), __('stock_transfers.notifications.cannot_approve')));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label(__('stock_transfers.actions.reject'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->schema([
                Textarea::make('reason')
                    ->label(__('common.fields.reason'))
                    ->rows(2),
            ])
            ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::PendingApproval
                && auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]))
            ->action(fn (array $data, StockTransfer $record) => self::run($record, fn () => $record->reject($data['reason'] ?? null), __('stock_transfers.notifications.rejected'), __('stock_transfers.notifications.cannot_reject')));
    }

    public static function markInTransit(): Action
    {
        return Action::make('markInTransit')
            ->label(__('stock_transfers.actions.mark_in_transit'))
            ->icon('heroicon-o-truck')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(__('stock_transfers.mark_in_transit_description'))
            ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::Approved)
            ->action(fn (StockTransfer $record) => self::run($record, fn () => $record->markInTransit(), __('stock_transfers.notifications.marked_in_transit'), __('stock_transfers.notifications.cannot_mark_in_transit')));
    }

    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label(__('stock_transfers.actions.cancel_transfer'))
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (StockTransfer $record) => in_array($record->status, [
                StockTransferStatus::Draft,
                StockTransferStatus::PendingApproval,
                StockTransferStatus::Approved,
            ], true))
            ->action(fn (StockTransfer $record) => self::run($record, fn () => $record->cancel(), __('stock_transfers.notifications.cancelled'), __('stock_transfers.notifications.cannot_cancel')));
    }
}
