<?php

namespace App\Services\AiAssistant;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\SaleItem;

/**
 * Read-only "tools" the AI assistant can call to answer business questions.
 * Every method here only ever queries already-tenant-scoped Eloquent
 * models (the assistant page only ever runs inside an initialized tenant
 * context, same as every other page in the business panel) — there is no
 * write path and no raw SQL the model could influence.
 *
 * List-returning tools are capped (see LIST_LIMIT) to keep token usage
 * bounded; each response reports the true total alongside the capped list
 * so the model can tell the user "showing top N of X" rather than implying
 * completeness.
 */
class StoxAssistantTools
{
    protected const LIST_LIMIT = 20;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            [
                'name' => 'get_profit_and_loss',
                'description' => "Get revenue, cost of goods sold, gross profit, expenses, and net profit for a date range. Use this for questions about profit, margin, or overall financial performance.",
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'from' => ['type' => 'string', 'description' => 'Start date, YYYY-MM-DD.'],
                        'until' => ['type' => 'string', 'description' => 'End date, YYYY-MM-DD.'],
                    ],
                    'required' => ['from', 'until'],
                ],
            ],
            [
                'name' => 'get_sales_summary',
                'description' => 'Get total revenue, number of sales, and top-selling products for a date range, optionally at one location. Use this for questions about sales performance or best-selling products.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'from' => ['type' => 'string', 'description' => 'Start date, YYYY-MM-DD.'],
                        'until' => ['type' => 'string', 'description' => 'End date, YYYY-MM-DD.'],
                        'location_name' => ['type' => 'string', 'description' => 'Optional. Filter to one location by name (case-insensitive, partial match ok). Omit for all locations combined.'],
                    ],
                    'required' => ['from', 'until'],
                ],
            ],
            [
                'name' => 'get_low_stock_products',
                'description' => 'List products at or below their reorder level (including out-of-stock). Use this for questions about what needs restocking.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location_name' => ['type' => 'string', 'description' => 'Optional. Filter to one location by name. Omit for all locations combined.'],
                    ],
                ],
            ],
            [
                'name' => 'get_expiring_batches',
                'description' => 'List product batches expiring within a number of days (includes already-expired). Use this for questions about expiry or waste risk.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'days' => ['type' => 'integer', 'description' => 'How many days ahead to check. Defaults to 30 if not specified.'],
                        'location_name' => ['type' => 'string', 'description' => 'Optional. Filter to one location by name. Omit for all locations combined.'],
                    ],
                ],
            ],
            [
                'name' => 'get_inventory_valuation',
                'description' => 'Get total stock value and the highest-value stock holdings. Use this for questions about how much inventory is worth or which products tie up the most capital.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location_name' => ['type' => 'string', 'description' => 'Optional. Filter to one location by name. Omit for all locations combined.'],
                    ],
                ],
            ],
            [
                'name' => 'get_customer_balance',
                'description' => "Look up a customer's outstanding balance and credit limit by name. Use this for questions about what a specific customer owes.",
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => "The customer's name, or part of it."],
                    ],
                    'required' => ['name'],
                ],
            ],
            [
                'name' => 'list_locations',
                'description' => 'List every location (store/warehouse) this business has. Use this to check the exact name of a location before filtering another tool by it.',
                // An empty PHP array encodes to JSON `[]`, but Gemini's
                // schema requires `properties` to be a JSON object (`{}`)
                // even when empty — (object) forces that encoding.
                'parameters' => ['type' => 'object', 'properties' => (object) []],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function call(string $name, array $args): array
    {
        return match ($name) {
            'get_profit_and_loss' => $this->getProfitAndLoss($args),
            'get_sales_summary' => $this->getSalesSummary($args),
            'get_low_stock_products' => $this->getLowStockProducts($args),
            'get_expiring_batches' => $this->getExpiringBatches($args),
            'get_inventory_valuation' => $this->getInventoryValuation($args),
            'get_customer_balance' => $this->getCustomerBalance($args),
            'list_locations' => $this->listLocations(),
            default => ['error' => "Unknown tool: {$name}"],
        };
    }

    protected function getProfitAndLoss(array $args): array
    {
        $from = $args['from'] ?? null;
        $until = $args['until'] ?? null;

        $items = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed)
            ->when($from, fn ($q, $date) => $q->whereDate('sales.sale_date', '>=', $date))
            ->when($until, fn ($q, $date) => $q->whereDate('sales.sale_date', '<=', $date))
            ->select('sale_items.*')
            ->with('product')
            ->get();

        $revenue = $items->sum(fn (SaleItem $item) => $item->total());
        $cogs = $items->sum(fn (SaleItem $item) => $item->cogsAmount());
        $grossProfit = $revenue - $cogs;

        $expenses = (float) Expense::query()
            ->when($from, fn ($q, $date) => $q->whereDate('expense_date', '>=', $date))
            ->when($until, fn ($q, $date) => $q->whereDate('expense_date', '<=', $date))
            ->sum('amount');

        $netProfit = $grossProfit - $expenses;

        return [
            'currency' => tenant('currency')?->value,
            'from' => $from,
            'until' => $until,
            'revenue' => round($revenue, 2),
            'cost_of_goods_sold' => round($cogs, 2),
            'gross_profit' => round($grossProfit, 2),
            'gross_margin_percent' => $revenue > 0 ? round(($grossProfit / $revenue) * 100, 1) : 0,
            'expenses' => round($expenses, 2),
            'net_profit' => round($netProfit, 2),
            'net_margin_percent' => $revenue > 0 ? round(($netProfit / $revenue) * 100, 1) : 0,
        ];
    }

    protected function getSalesSummary(array $args): array
    {
        $from = $args['from'] ?? null;
        $until = $args['until'] ?? null;
        [$locationId, $locationError] = $this->resolveLocation($args['location_name'] ?? null);

        if ($locationError) {
            return $locationError;
        }

        $items = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed)
            ->when($from, fn ($q, $date) => $q->whereDate('sales.sale_date', '>=', $date))
            ->when($until, fn ($q, $date) => $q->whereDate('sales.sale_date', '<=', $date))
            ->when($locationId, fn ($q, $id) => $q->where('sales.location_id', $id))
            ->select('sale_items.*')
            ->with('product')
            ->get();

        $revenue = $items->sum(fn (SaleItem $item) => $item->total());
        $salesCount = $items->pluck('sale_id')->unique()->count();
        $totalQuantity = (float) $items->sum('quantity');

        $topProducts = $items->groupBy('product_id')
            ->map(fn ($group) => [
                'name' => $group->first()->product?->name ?? 'Unknown product',
                'revenue' => round($group->sum(fn (SaleItem $item) => $item->total()), 2),
                'quantity' => (float) $group->sum('quantity'),
            ])
            ->sortByDesc('revenue')
            ->take(self::LIST_LIMIT)
            ->values()
            ->all();

        return [
            'currency' => tenant('currency')?->value,
            'from' => $from,
            'until' => $until,
            'revenue' => round($revenue, 2),
            'sales_count' => $salesCount,
            'total_items_sold' => $totalQuantity,
            'average_order_value' => $salesCount > 0 ? round($revenue / $salesCount, 2) : 0,
            'top_products' => $topProducts,
        ];
    }

    protected function getLowStockProducts(array $args): array
    {
        [$locationId, $locationError] = $this->resolveLocation($args['location_name'] ?? null);

        if ($locationError) {
            return $locationError;
        }

        $locationClause = $locationId ? ' and product_batches.location_id = ?' : '';

        $products = Product::query()
            ->where('is_active', true)
            ->withSum(['batches as stock_on_hand' => fn ($query) => $query
                ->where('is_active', true)
                ->when($locationId, fn ($query, $id) => $query->where('location_id', $id))], 'quantity')
            ->whereRaw(
                "(select coalesce(sum(quantity), 0) from product_batches where product_batches.product_id = products.id and product_batches.is_active = 1{$locationClause}) <= 0
                or (reorder_level is not null and (select coalesce(sum(quantity), 0) from product_batches where product_batches.product_id = products.id and product_batches.is_active = 1{$locationClause}) <= reorder_level)",
                $locationId ? [$locationId, $locationId] : []
            )
            ->orderBy('stock_on_hand')
            ->get();

        return [
            'total_count' => $products->count(),
            'products' => $products->take(self::LIST_LIMIT)->map(fn ($p) => [
                'name' => $p->name,
                'sku' => $p->sku,
                'on_hand' => (float) ($p->stock_on_hand ?? 0),
                'reorder_level' => $p->reorder_level !== null ? (float) $p->reorder_level : null,
            ])->values()->all(),
        ];
    }

    protected function getExpiringBatches(array $args): array
    {
        $days = (int) ($args['days'] ?? 30);
        [$locationId, $locationError] = $this->resolveLocation($args['location_name'] ?? null);

        if ($locationError) {
            return $locationError;
        }

        $batches = ProductBatch::query()
            ->where('is_active', true)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days))
            ->when($locationId, fn ($q, $id) => $q->where('location_id', $id))
            ->with('product')
            ->orderBy('expiry_date')
            ->get();

        return [
            'days' => $days,
            'total_count' => $batches->count(),
            'batches' => $batches->take(self::LIST_LIMIT)->map(fn (ProductBatch $b) => [
                'product_name' => $b->product?->name ?? 'Unknown product',
                'batch_number' => $b->batch_number,
                'expiry_date' => $b->expiry_date?->toDateString(),
                'days_until_expiry' => now()->startOfDay()->diffInDays($b->expiry_date, false),
                'quantity' => (float) $b->quantity,
            ])->values()->all(),
        ];
    }

    protected function getInventoryValuation(array $args): array
    {
        [$locationId, $locationError] = $this->resolveLocation($args['location_name'] ?? null);

        if ($locationError) {
            return $locationError;
        }

        $rows = ProductBatch::query()
            ->with('product')
            ->where('is_active', true)
            ->when($locationId, fn ($q, $id) => $q->where('location_id', $id))
            ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(quantity * unit_cost) as total_value')
            ->groupBy('product_id')
            ->get();

        $totalValue = $rows->sum('total_value');
        $totalQuantity = $rows->sum('total_quantity');

        return [
            'currency' => tenant('currency')?->value,
            'total_value' => round($totalValue, 2),
            'total_quantity' => (float) $totalQuantity,
            'products_tracked' => $rows->count(),
            'top_holdings' => $rows->sortByDesc('total_value')->take(self::LIST_LIMIT)->map(fn ($row) => [
                'product_name' => $row->product?->name ?? 'Unknown product',
                'value' => round((float) $row->total_value, 2),
                'quantity' => (float) $row->total_quantity,
            ])->values()->all(),
        ];
    }

    protected function getCustomerBalance(array $args): array
    {
        $name = trim($args['name'] ?? '');

        if ($name === '') {
            return ['error' => 'No customer name given.'];
        }

        $matches = Customer::query()->where('name', 'like', "%{$name}%")->limit(self::LIST_LIMIT)->get();

        if ($matches->isEmpty()) {
            return ['found' => false, 'message' => "No customer found matching '{$name}'."];
        }

        if ($matches->count() > 1) {
            return [
                'found' => 'multiple',
                'message' => 'Multiple customers matched — ask the user which one they mean.',
                'matches' => $matches->map(fn (Customer $c) => ['name' => $c->name, 'balance' => round($c->balance(), 2)])->all(),
            ];
        }

        $customer = $matches->first();

        return [
            'found' => true,
            'currency' => tenant('currency')?->value,
            'name' => $customer->name,
            'balance' => round($customer->balance(), 2),
            'credit_limit' => $customer->credit_limit !== null ? (float) $customer->credit_limit : null,
        ];
    }

    protected function listLocations(): array
    {
        return [
            'locations' => Location::query()->where('is_active', true)->get()->map(fn (Location $l) => [
                'name' => $l->name,
                'type' => $l->type?->value,
            ])->all(),
        ];
    }

    /**
     * @return array{0: ?int, 1: ?array<string, mixed>} [locationId, errorResponseOrNull]
     */
    protected function resolveLocation(?string $name): array
    {
        if (blank($name)) {
            return [null, null];
        }

        $location = Location::query()->where('name', 'like', "%{$name}%")->first();

        if (! $location) {
            return [null, ['error' => "No location found matching '{$name}'. Call list_locations to see valid names."]];
        }

        return [$location->id, null];
    }
}
