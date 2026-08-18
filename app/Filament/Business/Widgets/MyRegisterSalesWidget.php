<?php

namespace App\Filament\Business\Widgets;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The one thing a Cashier actually sees on the dashboard today —
 * DashboardKpisWidget/ExecutiveKpisWidget etc. are all Owner/Manager-only,
 * so without this a Cashier lands on a page with nothing but the location
 * picker. Scoped to sales *they* rang up (created_by/creator_type), not
 * the whole location, so it reads as "my shift" rather than a KPI they
 * don't have authority over.
 */
class MyRegisterSalesWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager, UserRole::Cashier]);
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        $todaysSales = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereDate('sale_date', today())
            ->where('created_by', $user?->getKey())
            ->where('creator_type', $user?->getMorphClass())
            ->with('items.product')
            ->get();

        $revenue = $todaysSales->sum(fn (Sale $sale) => $sale->total());

        return [
            Stat::make(__('dashboard.kpis.my_sales_today'), $todaysSales->count()),
            Stat::make(__('dashboard.kpis.my_revenue_today'), tenant('currency')?->value.' '.number_format($revenue, 2)),
        ];
    }
}
