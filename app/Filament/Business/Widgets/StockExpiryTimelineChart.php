<?php

namespace App\Filament\Business\Widgets;

use App\Enums\UserRole;
use App\Models\ProductBatch;
use Filament\Widgets\ChartWidget;

class StockExpiryTimelineChart extends ChartWidget
{
    protected ?string $heading = 'Stock expiry timeline';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper]);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $today = today();

        $row = ProductBatch::query()
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->selectRaw('
                SUM(CASE WHEN expiry_date < ? THEN quantity ELSE 0 END) as expired,
                SUM(CASE WHEN expiry_date >= ? AND expiry_date < ? THEN quantity ELSE 0 END) as within_30,
                SUM(CASE WHEN expiry_date >= ? AND expiry_date < ? THEN quantity ELSE 0 END) as within_60,
                SUM(CASE WHEN expiry_date >= ? AND expiry_date < ? THEN quantity ELSE 0 END) as within_90,
                SUM(CASE WHEN expiry_date >= ? THEN quantity ELSE 0 END) as beyond_90
            ', [
                $today,
                $today, $today->copy()->addDays(30),
                $today->copy()->addDays(30), $today->copy()->addDays(60),
                $today->copy()->addDays(60), $today->copy()->addDays(90),
                $today->copy()->addDays(90),
            ])
            ->first();

        return [
            'datasets' => [
                [
                    'label' => 'Quantity',
                    'data' => [
                        round((float) $row->expired, 2),
                        round((float) $row->within_30, 2),
                        round((float) $row->within_60, 2),
                        round((float) $row->within_90, 2),
                        round((float) $row->beyond_90, 2),
                    ],
                    'backgroundColor' => ['#991b1b', '#ef4444', '#f59e0b', '#eab308', '#22c55e'],
                ],
            ],
            'labels' => ['Already expired', '0–30 days', '31–60 days', '61–90 days', '90+ days'],
        ];
    }
}
