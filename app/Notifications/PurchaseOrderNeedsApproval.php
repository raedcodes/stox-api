<?php

namespace App\Notifications;

use App\Filament\Business\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderNeedsApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected PurchaseOrder $purchaseOrder) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.purchase_order_approval.subject'))
            ->greeting(__('notifications.purchase_order_approval.greeting'))
            ->line(__('notifications.purchase_order_approval.line', [
                'po_number' => $this->purchaseOrder->po_number,
                'supplier' => $this->purchaseOrder->supplier?->name,
                'total' => tenant('currency')?->value.' '.number_format($this->purchaseOrder->total(), 2),
            ]))
            ->action(
                __('notifications.purchase_order_approval.action'),
                // See LowStockAndExpiryDigest for why this builds a relative
                // path + Tenant::url() rather than an absolute Filament URL.
                tenant()->url(PurchaseOrderResource::getUrl('view', ['record' => $this->purchaseOrder], isAbsolute: false, panel: 'business')),
            );
    }
}
