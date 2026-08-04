<?php

namespace App\Filament\Business\Widgets;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Sale;
use Filament\Widgets\ChartWidget;

class SalesRevenueTrendChart extends ChartWidget
{
    protected ?string $heading = 'Revenue vs. cost of goods (last 30 days)';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn (int $i) => today()->subDays($i));

        $salesByDay = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->where('sale_date', '>=', today()->subDays(29)->startOfDay())
            ->with('items.product')
            ->get()
            ->groupBy(fn (Sale $sale) => $sale->sale_date->toDateString());

        $revenue = [];
        $cogs = [];

        foreach ($days as $day) {
            $daySales = $salesByDay->get($day->toDateString(), collect());

            $revenue[] = round($daySales->sum(fn (Sale $sale) => $sale->total()), 2);
            $cogs[] = round(
                $daySales->sum(fn (Sale $sale) => $sale->items->sum(
                    fn ($item) => (float) $item->quantity * (float) $item->product?->cost_price
                )),
                2,
            );
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $revenue,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Cost of goods sold',
                    'data' => $cogs,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $days->map(fn ($day) => $day->format('M j'))->all(),
        ];
    }
}
