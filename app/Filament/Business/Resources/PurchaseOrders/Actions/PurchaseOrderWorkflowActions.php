<?php

namespace App\Filament\Business\Resources\PurchaseOrders\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\HasApprovalWorkflowActions;
use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;

/**
 * The Draft -> PendingApproval -> Approved(=Ordered) -> Received chain's
 * approval-gate actions — mirrors StockTransferWorkflowActions' shape and
 * reasoning, so both workflows read the same way in the UI. Receiving
 * itself is unchanged (ReceiveStockAction already only shows once Ordered).
 */
class PurchaseOrderWorkflowActions
{
    use HasApprovalWorkflowActions;

    public static function submitForApproval(): Action
    {
        return Action::make('submitForApproval')
            ->label(__('purchase_orders.actions.submit_for_approval'))
            ->icon('heroicon-o-paper-airplane')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::Draft)
            ->action(fn (PurchaseOrder $record) => self::run($record, fn () => $record->submitForApproval(), __('purchase_orders.notifications.submitted'), __('purchase_orders.notifications.cannot_submit')));
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label(__('purchase_orders.actions.approve'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(__('purchase_orders.approve_description'))
            ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::PendingApproval
                && auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]))
            ->action(fn (PurchaseOrder $record) => self::run($record, fn () => $record->approve(), __('purchase_orders.notifications.approved'), __('purchase_orders.notifications.cannot_approve')));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label(__('purchase_orders.actions.reject'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->schema([
                Textarea::make('reason')
                    ->label(__('common.fields.reason'))
                    ->rows(2),
            ])
            ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::PendingApproval
                && auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]))
            ->action(fn (array $data, PurchaseOrder $record) => self::run($record, fn () => $record->reject($data['reason'] ?? null), __('purchase_orders.notifications.rejected'), __('purchase_orders.notifications.cannot_reject')));
    }

    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label(__('purchase_orders.actions.cancel_order'))
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (PurchaseOrder $record) => in_array($record->status, [
                PurchaseOrderStatus::Draft,
                PurchaseOrderStatus::PendingApproval,
                PurchaseOrderStatus::Ordered,
            ], true))
            ->action(fn (PurchaseOrder $record) => self::run($record, fn () => $record->cancel(), __('purchase_orders.notifications.cancelled'), __('purchase_orders.notifications.cannot_cancel')));
    }
}
