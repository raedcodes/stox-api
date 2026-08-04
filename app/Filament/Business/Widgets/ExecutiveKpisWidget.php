<?php

namespace App\Filament\Business\Widgets;

use App\Enums\CustomerTransactionType;
use App\Enums\SaleStatus;
use App\Enums\SupplierTransactionType;
use App\Enums\UserRole;
use App\Filament\Business\Widgets\Concerns\HasTrendComparison;
use App\Models\CustomerTransaction;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SupplierTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ExecutiveKpisWidget extends StatsOverviewWidget
{
    use HasTrendComparison;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $currency = tenant('currency')?->value;

        $thisMonthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        $thisMonthSales = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->where('sale_date', '>=', $thisMonthStart)
            ->get();

        $lastMonthSales = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereBetween('sale_date', [$lastMonthStart, $lastMonthEnd])
            ->get();

        $thisMonthRevenue = $thisMonthSales->sum(fn (Sale $sale) => $sale->total());
        $lastMonthRevenue = $lastMonthSales->sum(fn (Sale $sale) => $sale->total());

        $thisMonthCogs = $this->cogsSince($thisMonthStart);
        $grossProfit = $thisMonthRevenue - $thisMonthCogs;
        $grossMarginPercent = $thisMonthRevenue > 0 ? ($grossProfit / $thisMonthRevenue) * 100 : 0;

        $accountsReceivable = $this->outstandingBalance(
            CustomerTransaction::class,
            CustomerTransactionType::CreditSale,
            CustomerTransactionType::reducingTypes(),
        );

        $accountsPayable = $this->outstandingBalance(
            SupplierTransaction::class,
            SupplierTransactionType::Charge,
            SupplierTransactionType::reducingTypes(),
        );

        $inventoryValue = (float) ProductBatch::query()
            ->where('is_active', true)
            ->selectRaw('SUM(quantity * unit_cost) as total')
            ->value('total');

        return [
            Stat::make('Sales this month', $thisMonthSales->count())
                ->description($this->trendDescription($thisMonthSales->count(), $lastMonthSales->count(), 'last month'))
                ->descriptionIcon($this->trendIcon($thisMonthSales->count(), $lastMonthSales->count()))
                ->descriptionColor($this->trendColor($thisMonthSales->count(), $lastMonthSales->count())),
            Stat::make('Revenue this month', "{$currency} ".number_format($thisMonthRevenue, 2))
                ->description($this->trendDescription($thisMonthRevenue, $lastMonthRevenue, 'last month'))
                ->descriptionIcon($this->trendIcon($thisMonthRevenue, $lastMonthRevenue))
                ->descriptionColor($this->trendColor($thisMonthRevenue, $lastMonthRevenue)),
            Stat::make('Gross profit this month', "{$currency} ".number_format($grossProfit, 2))
                ->description(number_format($grossMarginPercent, 1).'% margin')
                ->descriptionColor($grossMarginPercent >= 0 ? 'success' : 'danger')
                ->color($grossProfit >= 0 ? 'success' : 'danger'),
            Stat::make('Accounts receivable', "{$currency} ".number_format($accountsReceivable, 2))
                ->description('Owed by customers')
                ->color($accountsReceivable > 0 ? 'warning' : 'success'),
            Stat::make('Accounts payable', "{$currency} ".number_format($accountsPayable, 2))
                ->description('Owed to suppliers')
                ->color($accountsPayable > 0 ? 'warning' : 'success'),
            Stat::make('Inventory value', "{$currency} ".number_format($inventoryValue, 2))
                ->description('Current stock, at cost'),
        ];
    }

    /**
     * Cost of goods sold since a given date, using the same live-cost
     * method as the Profit & Loss report — kept identical on purpose so
     * this KPI never disagrees with that report's own number.
     */
    protected function cogsSince(Carbon $since): float
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed)
            ->where('sales.sale_date', '>=', $since)
            ->select('sale_items.*')
            ->with('product')
            ->get()
            ->sum(fn (SaleItem $item) => (float) $item->quantity * (float) $item->product->cost_price);
    }

    /**
     * Total outstanding balance across every customer/supplier in one
     * aggregate query — mirrors Customer::balance()/Supplier::balance(),
     * but tenant-wide instead of per-record, to avoid an N+1 loop.
     *
     * @param  class-string  $transactionModel
     * @param  array<CustomerTransactionType|SupplierTransactionType>  $reducingTypes
     */
    protected function outstandingBalance(string $transactionModel, CustomerTransactionType|SupplierTransactionType $increasingType, array $reducingTypes): float
    {
        $totals = $transactionModel::query()
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $reducing = array_sum(array_map(
            fn ($type) => (float) ($totals[$type->value] ?? 0),
            $reducingTypes,
        ));

        return (float) ($totals[$increasingType->value] ?? 0) - $reducing;
    }
}
