<?php

namespace App\Notifications;

use App\Filament\Business\Pages\StockLevels;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LowStockAndExpiryDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, Product>  $lowStockProducts
     * @param  Collection<int, ProductBatch>  $expiringBatches
     */
    public function __construct(
        protected Collection $lowStockProducts,
        protected Collection $expiringBatches,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('notifications.daily_digest.subject'))
            ->greeting(__('notifications.daily_digest.greeting'));

        if ($this->lowStockProducts->isNotEmpty()) {
            $mail->line(__('notifications.daily_digest.low_stock_heading', ['count' => $this->lowStockProducts->count()]));

            foreach ($this->lowStockProducts as $product) {
                $mail->line("- {$product->name} ({$product->sku}): ".number_format((float) ($product->stock_on_hand ?? 0), 2)." on hand");
            }
        }

        if ($this->expiringBatches->isNotEmpty()) {
            $mail->line(__('notifications.daily_digest.expiring_heading', ['count' => $this->expiringBatches->count()]));

            foreach ($this->expiringBatches as $batch) {
                $mail->line("- {$batch->product?->name} (batch {$batch->batch_number}): expires {$batch->expiry_date?->toDateString()}");
            }
        }

        // Explicit panel + relative path: this renders inside a queued job
        // with no active HTTP request, so Filament has no "current panel"
        // to guess from, and generating an absolute URL would resolve to
        // config('app.url') instead of this tenant's own subdomain — see
        // Tenant::url(), which builds the correct host instead.
        $path = StockLevels::getUrl(panel: 'business', isAbsolute: false);

        return $mail->action(__('notifications.daily_digest.action'), tenant()->url($path));
    }
}
