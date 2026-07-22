<?php

namespace App\Filament\Business\Widgets;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardKpisWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $todaysSales = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereDate('sale_date', today())
            ->get();

        $lowStockCount = Product::query()
            ->where('is_active', true)
            ->whereRaw(
                '(select coalesce(sum(quantity), 0) from product_batches where product_batches.product_id = products.id and product_batches.is_active = 1) <= 0
                or (reorder_level is not null and (select coalesce(sum(quantity), 0) from product_batches where product_batches.product_id = products.id and product_batches.is_active = 1) <= reorder_level)'
            )
            ->count();

        return [
            Stat::make("Today's sales", $todaysSales->count()),
            Stat::make('Today\'s revenue', tenant('currency')?->value.' '.number_format($todaysSales->sum(fn (Sale $sale) => $sale->total()), 2)),
            Stat::make('Low / out of stock', $lowStockCount)
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
