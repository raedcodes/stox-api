<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Spatie\Backup\Config\Config;
use Spatie\Backup\Tasks\Backup\BackupJobFactory;

class BackupTenantDatabases extends Command
{
    protected $signature = 'backup:tenants';

    protected $description = 'Back up each tenant\'s database individually';

    public function handle(): void
    {
        tenancy()->runForMultiple(null, function (Tenant $tenant) {
            $this->info("Backing up tenant: {$tenant->getTenantKey()}");

            $backupConfig = config('backup.backup');
            $backupConfig['source']['databases'] = ['tenant'];

            $config = Config::fromArray([
                'backup' => $backupConfig,
                'notifications' => config('backup.notifications'),
                'monitor_backups' => config('backup.monitor_backups'),
                'cleanup' => config('backup.cleanup'),
            ]);

            BackupJobFactory::createFromConfig($config)
                ->dontBackupFilesystem()
                ->onlyDbName(['tenant'])
                ->setFilename("tenant-{$tenant->getTenantKey()}-".now()->format('Y-m-d-H-i-s').'.zip')
                ->run();
        });
    }
}
