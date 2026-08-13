<?php

namespace App\Filament\Business\Widgets;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\SaleItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class TopProductsChart extends ChartWidget
{
    public function getHeading(): string|Htmlable|null
    {
        return __('dashboard.widgets.top_products_chart_heading');
    }

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', SaleStatus::Completed)
            ->where('sales.sale_date', '>=', now()->subDays(30))
            ->selectRaw('products.name, SUM(sale_items.quantity * sale_items.unit_price) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.chart.revenue'),
                    'data' => $rows->map(fn ($row) => round((float) $row->revenue, 2))->all(),
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => $rows->pluck('name')->all(),
        ];
    }
}
