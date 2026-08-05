<?php

namespace App\Filament\Business\Widgets;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Filament\Business\Widgets\Concerns\HasTrendComparison;
use App\Models\Product;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardKpisWidget extends StatsOverviewWidget
{
    use HasTrendComparison;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected function getStats(): array
    {
        // ->total() reads $this->items (and each item's ->product), so both
        // must be eager-loaded here — otherwise every sale summed below
        // triggers its own lazy-loaded items + product queries.
        $todaysSales = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereDate('sale_date', today())
            ->with('items.product')
            ->get();

        $yesterdaysSales = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereDate('sale_date', today()->subDay())
            ->with('items.product')
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
                ->description($this->trendDescription($todaysSales->count(), $yesterdaysSales->count(), 'yesterday'))
                ->descriptionIcon($this->trendIcon($todaysSales->count(), $yesterdaysSales->count()))
                ->descriptionColor($this->trendColor($todaysSales->count(), $yesterdaysSales->count())),
            Stat::make('Today\'s revenue', tenant('currency')?->value.' '.number_format($todaysRevenue, 2))
                ->description($this->trendDescription($todaysRevenue, $yesterdaysRevenue, 'yesterday'))
                ->descriptionIcon($this->trendIcon($todaysRevenue, $yesterdaysRevenue))
                ->descriptionColor($this->trendColor($todaysRevenue, $yesterdaysRevenue)),
            Stat::make('Low / out of stock', $lowStockCount)
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
