<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\LowStockAndExpiryDigest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendDailyOperationsDigest extends Command
{
    protected $signature = 'digest:send';

    protected $description = "Email each tenant's Owner/Manager a daily low-stock and expiring-batch digest";

    public function handle(): void
    {
        tenancy()->runForMultiple(null, function (Tenant $tenant) {
            $lowStockProducts = Product::query()->lowStock()->get();
            $expiringBatches = ProductBatch::query()->expiringWithin(30)->with('product')->get();

            if ($lowStockProducts->isEmpty() && $expiringBatches->isEmpty()) {
                $this->info("Tenant {$tenant->getTenantKey()}: nothing to report, skipping.");

                return;
            }

            $owners = User::where('tenant_id', tenant('id'))->get()->filter(fn (User $u) => $u->hasRole(UserRole::Owner));
            $managers = Staff::where('is_active', true)->get()->filter(fn (Staff $s) => $s->hasRole(UserRole::Manager));
            $recipients = $owners->concat($managers);

            if ($recipients->isEmpty()) {
                $this->info("Tenant {$tenant->getTenantKey()}: no Owner/Manager to notify, skipping.");

                return;
            }

            Notification::send($recipients, new LowStockAndExpiryDigest($lowStockProducts, $expiringBatches));

            $this->info("Tenant {$tenant->getTenantKey()}: digest sent to {$recipients->count()} recipient(s).");
        });
    }
}
