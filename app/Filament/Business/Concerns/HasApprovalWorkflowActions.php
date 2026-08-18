<?php

namespace App\Filament\Business\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * The try/catch/notify wrapper every approval-workflow transition action
 * runs through — shared by PurchaseOrderWorkflowActions and
 * StockTransferWorkflowActions, which previously each carried a
 * byte-for-byte identical copy of this method.
 */
trait HasApprovalWorkflowActions
{
    protected static function run(Model $record, callable $transition, string $successTitle, string $failureTitle): void
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
