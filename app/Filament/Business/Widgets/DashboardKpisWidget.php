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

        $yesterdaysSales = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereDate('sale_date', today()->subDay())
            ->get();

        $todaysRevenue = $todaysSales->sum(fn (Sale $sale) => $sale->total());
        $yesterdaysRevenue = $yesterdaysSales->sum(fn (Sale $sale) => $sale->total());

        $lowStockCount = Product::query()
            ->where('is_active', true)
            ->whereRaw(
                '(select coalesce(sum(quantity), 0) from product_batches where product_batches.product_id = products.id and product_batches.is_active = 1) <= 0
                or (reorder_level is not null and (select coalesce(sum(quantity), 0) from product_batches where product_batches.product_id = products.id and product_batches.is_active = 1) <= reorder_level)'
            )
            ->count();

        return [
            Stat::make("Today's sales", $todaysSales->count())
                ->description($this->trendDescription($todaysSales->count(), $yesterdaysSales->count()))
                ->descriptionIcon($this->trendIcon($todaysSales->count(), $yesterdaysSales->count()))
                ->descriptionColor($this->trendColor($todaysSales->count(), $yesterdaysSales->count())),
            Stat::make('Today\'s revenue', tenant('currency')?->value.' '.number_format($todaysRevenue, 2))
                ->description($this->trendDescription($todaysRevenue, $yesterdaysRevenue))
                ->descriptionIcon($this->trendIcon($todaysRevenue, $yesterdaysRevenue))
                ->descriptionColor($this->trendColor($todaysRevenue, $yesterdaysRevenue)),
            Stat::make('Low / out of stock', $lowStockCount)
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }

    /**
     * A human-readable "vs. yesterday" comparison for a today/yesterday pair.
     */
    protected function trendDescription(float $today, float $yesterday): string
    {
        if ($today == $yesterday) {
            return 'Same as yesterday';
        }

        if ($yesterday == 0.0) {
            return 'Up from zero yesterday';
        }

        $percent = (($today - $yesterday) / $yesterday) * 100;

        return number_format(abs($percent), 1).'% '.($percent >= 0 ? 'up' : 'down').' vs. yesterday';
    }

    protected function trendIcon(float $today, float $yesterday): string
    {
        return $today >= $yesterday ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    protected function trendColor(float $today, float $yesterday): string
    {
        return $today >= $yesterday ? 'success' : 'danger';
    }
}
