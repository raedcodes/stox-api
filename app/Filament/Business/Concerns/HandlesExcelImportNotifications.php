<?php

namespace App\Filament\Business\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

/**
 * Shared "N imported / M failed" notification for the Products and Opening
 * Stock bulk-import actions. $langPrefix points at each page's own
 * `import.*` lang keys (e.g. 'products.import', 'opening_stock_entry.import')
 * so the generic success/partial/failed wording stays translated per page
 * while the row-failure formatting logic itself is written once.
 */
trait HandlesExcelImportNotifications
{
    protected const MAX_DISPLAYED_ROW_ERRORS = 5;

    /**
     * @param  Collection<int, Failure>  $failures
     */
    protected function notifyImportResult(string $langPrefix, int $importedCount, Collection $failures): void
    {
        $failedCount = $failures->count();

        if ($failedCount === 0) {
            Notification::make()
                ->title(__($langPrefix.'.success_title'))
                ->body(__($langPrefix.'.success_body', ['count' => $importedCount]))
                ->success()
                ->send();

            return;
        }

        $body = $this->formatRowErrors($langPrefix, $failures);

        if ($importedCount === 0) {
            Notification::make()
                ->title(__($langPrefix.'.all_failed_title'))
                ->body($body)
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title(__($langPrefix.'.partial_title', ['imported' => $importedCount, 'failed' => $failedCount]))
            ->body($body)
            ->warning()
            ->persistent()
            ->send();
    }

    protected function notifyImportException(string $langPrefix, Throwable $e): void
    {
        Notification::make()
            ->title(__($langPrefix.'.failed_title'))
            ->body($e->getMessage())
            ->danger()
            ->persistent()
            ->send();
    }

    /**
     * @param  Collection<int, Failure>  $failures
     */
    protected function formatRowErrors(string $langPrefix, Collection $failures): string
    {
        $lines = $failures->take(self::MAX_DISPLAYED_ROW_ERRORS)
            ->map(fn (Failure $failure) => __($langPrefix.'.row_error', [
                'row' => $failure->row(),
                'message' => implode(' ', $failure->errors()),
            ]))
            ->implode('; ');

        $remaining = $failures->count() - self::MAX_DISPLAYED_ROW_ERRORS;

        if ($remaining > 0) {
            $lines .= '; '.__($langPrefix.'.more_errors', ['count' => $remaining]);
        }

        return $lines;
    }
}
