<?php

namespace App\Services\AiAssistant;

use App\Enums\CashSessionStatus;
use App\Enums\CustomerTransactionType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SaleStatus;
use App\Enums\SupplierTransactionType;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseOrder;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Services\Accounting\AgingCalculator;
use Illuminate\Support\Carbon;

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
                'description' => "Look up a specific customer's outstanding balance and credit limit by name, OR omit the name to see customers ranked by balance owed (highest first) — use this for 'who owes the most' style questions.",
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => "Optional. The customer's name, or part of it. Omit to list customers ranked by balance owed, highest first."],
                    ],
                ],
            ],
            [
                'name' => 'get_supplier_balance',
                'description' => "Look up how much this business owes a specific supplier by name, OR omit the name to see suppliers ranked by balance owed (highest first) — use this for 'who do we owe the most' style questions.",
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => "Optional. The supplier's name, or part of it. Omit to list suppliers ranked by balance owed, highest first."],
                    ],
                ],
            ],
            [
                'name' => 'get_expense_breakdown',
                'description' => 'Get total expenses for a date range, broken down by category (rent, utilities, salaries, etc). Use this for questions about spending or where money is going.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'from' => ['type' => 'string', 'description' => 'Start date, YYYY-MM-DD.'],
                        'until' => ['type' => 'string', 'description' => 'End date, YYYY-MM-DD.'],
                        'location_name' => ['type' => 'string', 'description' => 'Optional. Filter to one location by name. Omit for all locations combined.'],
                    ],
                    'required' => ['from', 'until'],
                ],
            ],
            [
                'name' => 'get_cash_session_status',
                'description' => "Check whether a location's cash till is currently open or closed, and if open, its opening float and expected cash right now. Use this for questions about the register/till status.",
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location_name' => ['type' => 'string', 'description' => 'Optional. Filter to one location by name. Omit to check every location.'],
                    ],
                ],
            ],
            [
                'name' => 'get_aging_summary',
                'description' => "Get an accounts receivable or accounts payable aging breakdown (Current / 1-30 / 31-60 / 61-90 / 90+ days overdue), plus the most overdue customers or suppliers. Use this for questions about overdue balances, collections risk, or how much of what's owed is overdue.",
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'side' => ['type' => 'string', 'enum' => ['receivable', 'payable'], 'description' => "'receivable' for what customers owe this business, 'payable' for what this business owes suppliers."],
                    ],
                    'required' => ['side'],
                ],
            ],
            [
                'name' => 'get_reorder_suggestions',
                'description' => 'List products that need restocking along with a suggested reorder quantity, based on each product\'s reorder level and current stock on hand. Use this for "what should I order" or restocking-decision questions.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location_name' => ['type' => 'string', 'description' => 'Optional. Filter to one location by name. Omit for all locations combined.'],
                    ],
                ],
            ],
            [
                'name' => 'get_purchase_order_status',
                'description' => 'List purchase orders, optionally filtered to one specific status. Defaults to every open order (pending approval, ordered, or partially received) if no status is given. Use this for questions about purchase order or ordering status.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => [
                            'type' => 'string',
                            'enum' => ['draft', 'pending_approval', 'ordered', 'partially_received', 'received', 'rejected', 'cancelled'],
                            'description' => 'Optional. Filter to one specific status. Omit to see all currently open orders.',
                        ],
                    ],
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
            'get_supplier_balance' => $this->getSupplierBalance($args),
            'get_expense_breakdown' => $this->getExpenseBreakdown($args),
            'get_cash_session_status' => $this->getCashSessionStatus($args),
            'get_aging_summary' => $this->getAgingSummary($args),
            'get_reorder_suggestions' => $this->getReorderSuggestions($args),
            'get_purchase_order_status' => $this->getPurchaseOrderStatus($args),
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

        $products = Product::query()
            ->lowStock($locationId)
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
            ->expiringWithin($days, $locationId)
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
            return [
                'currency' => tenant('currency')?->value,
                'customers_ranked_by_balance' => Customer::all()
                    ->map(fn (Customer $c) => ['name' => $c->name, 'balance' => round($c->balance(), 2)])
                    ->filter(fn (array $row) => $row['balance'] > 0.005)
                    ->sortByDesc('balance')
                    ->take(self::LIST_LIMIT)
                    ->values()
                    ->all(),
            ];
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

    protected function getSupplierBalance(array $args): array
    {
        $name = trim($args['name'] ?? '');

        if ($name === '') {
            return [
                'currency' => tenant('currency')?->value,
                'suppliers_ranked_by_balance' => Supplier::all()
                    ->map(fn (Supplier $s) => ['name' => $s->name, 'balance' => round($s->balance(), 2)])
                    ->filter(fn (array $row) => $row['balance'] > 0.005)
                    ->sortByDesc('balance')
                    ->take(self::LIST_LIMIT)
                    ->values()
                    ->all(),
            ];
        }

        $matches = Supplier::query()->where('name', 'like', "%{$name}%")->limit(self::LIST_LIMIT)->get();

        if ($matches->isEmpty()) {
            return ['found' => false, 'message' => "No supplier found matching '{$name}'."];
        }

        if ($matches->count() > 1) {
            return [
                'found' => 'multiple',
                'message' => 'Multiple suppliers matched — ask the user which one they mean.',
                'matches' => $matches->map(fn (Supplier $s) => ['name' => $s->name, 'balance' => round($s->balance(), 2)])->all(),
            ];
        }

        $supplier = $matches->first();

        return [
            'found' => true,
            'currency' => tenant('currency')?->value,
            'name' => $supplier->name,
            'balance' => round($supplier->balance(), 2),
            'payment_terms' => $supplier->payment_terms?->getLabel(),
        ];
    }

    protected function getExpenseBreakdown(array $args): array
    {
        $from = $args['from'] ?? null;
        $until = $args['until'] ?? null;
        [$locationId, $locationError] = $this->resolveLocation($args['location_name'] ?? null);

        if ($locationError) {
            return $locationError;
        }

        $expenses = Expense::query()
            ->when($from, fn ($q, $date) => $q->whereDate('expense_date', '>=', $date))
            ->when($until, fn ($q, $date) => $q->whereDate('expense_date', '<=', $date))
            ->when($locationId, fn ($q, $id) => $q->where('location_id', $id))
            ->get();

        $total = $expenses->sum(fn (Expense $e) => (float) $e->amount);

        $byCategory = $expenses->groupBy(fn (Expense $e) => $e->category->value)
            ->map(fn ($group, $categoryValue) => [
                'category' => $group->first()->category->getLabel(),
                'total' => round($group->sum(fn (Expense $e) => (float) $e->amount), 2),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'currency' => tenant('currency')?->value,
            'from' => $from,
            'until' => $until,
            'total_expenses' => round($total, 2),
            'by_category' => $byCategory,
        ];
    }

    protected function getCashSessionStatus(array $args): array
    {
        [$locationId, $locationError] = $this->resolveLocation($args['location_name'] ?? null);

        if ($locationError) {
            return $locationError;
        }

        $openSessions = CashSession::query()
            ->where('status', CashSessionStatus::Open)
            ->when($locationId, fn ($q, $id) => $q->where('location_id', $id))
            ->with('location')
            ->get();

        return [
            'currency' => tenant('currency')?->value,
            'open_sessions' => $openSessions->map(fn (CashSession $session) => [
                'location' => $session->location?->name,
                'opened_at' => $session->opened_at?->toDateTimeString(),
                'opening_float' => round((float) $session->opening_float, 2),
                'expected_cash_now' => round($session->expectedCash(), 2),
            ])->values()->all(),
        ];
    }

    /**
     * Mirrors AccountsReceivableAging/AccountsPayableAging's own row
     * computation exactly (same AgingCalculator, same due-date rules) so
     * a chat answer here can never disagree with those reports.
     */
    protected function getAgingSummary(array $args): array
    {
        $side = $args['side'] ?? null;

        if (! in_array($side, ['receivable', 'payable'], true)) {
            return ['error' => "side must be 'receivable' or 'payable'."];
        }

        $asOf = Carbon::now();

        if ($side === 'receivable') {
            $rows = Customer::query()->with('transactions')->get()->map(function (Customer $customer) use ($asOf) {
                $transactions = $customer->transactions->sortBy([['transaction_date', 'asc'], ['id', 'asc']])->values();

                $buckets = AgingCalculator::bucket(
                    $transactions,
                    CustomerTransactionType::CreditSale,
                    CustomerTransactionType::reducingTypes(),
                    fn ($transaction) => Carbon::parse($transaction->transaction_date),
                    $asOf,
                );

                return ['name' => $customer->name, 'buckets' => $buckets];
            });
        } else {
            $rows = Supplier::query()->with('transactions')->get()->map(function (Supplier $supplier) use ($asOf) {
                $transactions = $supplier->transactions->sortBy([['transaction_date', 'asc'], ['id', 'asc']])->values();
                $netDays = $supplier->payment_terms?->netDays() ?? 0;

                $buckets = AgingCalculator::bucket(
                    $transactions,
                    SupplierTransactionType::Charge,
                    SupplierTransactionType::reducingTypes(),
                    fn ($transaction) => Carbon::parse($transaction->transaction_date)->addDays($netDays),
                    $asOf,
                );

                return ['name' => $supplier->name, 'buckets' => $buckets];
            });
        }

        $rows = $rows->filter(fn (array $row) => $row['buckets']['total'] > 0.005)->sortByDesc(fn (array $row) => $row['buckets']['total'])->values();

        $totals = array_fill_keys([...AgingCalculator::BUCKETS, 'total'], 0.0);

        foreach ($rows as $row) {
            foreach ($totals as $key => $value) {
                $totals[$key] += $row['buckets'][$key];
            }
        }

        return [
            'currency' => tenant('currency')?->value,
            'side' => $side,
            'totals' => array_map(fn ($value) => round($value, 2), $totals),
            'most_overdue' => $rows->take(self::LIST_LIMIT)->map(fn (array $row) => [
                'name' => $row['name'],
                'total' => round($row['buckets']['total'], 2),
                'over_90_days' => round($row['buckets']['over_90'], 2),
            ])->values()->all(),
        ];
    }

    /**
     * Reuses the same "reorder to twice the reorder level" suggestion
     * CreatePurchaseOrder's deep-link quick-reorder flow already uses
     * (App\Filament\Business\Resources\PurchaseOrders\Pages\CreatePurchaseOrder::suggestedReorderQuantity()),
     * so the number the assistant says matches what actually gets
     * pre-filled if the user acts on it.
     */
    protected function getReorderSuggestions(array $args): array
    {
        [$locationId, $locationError] = $this->resolveLocation($args['location_name'] ?? null);

        if ($locationError) {
            return $locationError;
        }

        $suggestions = Product::query()
            ->lowStock($locationId)
            ->get()
            ->map(function (Product $product) {
                if ($product->reorder_level === null) {
                    return null;
                }

                $suggested = ((float) $product->reorder_level * 2) - (float) ($product->stock_on_hand ?? 0);

                if ($suggested <= 0) {
                    return null;
                }

                return [
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'on_hand' => (float) ($product->stock_on_hand ?? 0),
                    'reorder_level' => (float) $product->reorder_level,
                    'suggested_order_quantity' => round($suggested, 2),
                ];
            })
            ->filter()
            ->values();

        return [
            'total_count' => $suggestions->count(),
            'suggestions' => $suggestions->take(self::LIST_LIMIT)->all(),
        ];
    }

    protected function getPurchaseOrderStatus(array $args): array
    {
        $status = $args['status'] ?? null;
        $query = PurchaseOrder::query()->with('supplier');

        if ($status !== null) {
            $enum = PurchaseOrderStatus::tryFrom($status);

            if (! $enum) {
                return ['error' => "Unknown status '{$status}'."];
            }

            $query->where('status', $enum);
        } else {
            $query->whereIn('status', [
                PurchaseOrderStatus::PendingApproval,
                PurchaseOrderStatus::Ordered,
                PurchaseOrderStatus::PartiallyReceived,
            ]);
        }

        $orders = $query->orderBy('order_date')->get();

        return [
            'currency' => tenant('currency')?->value,
            'total_count' => $orders->count(),
            'purchase_orders' => $orders->take(self::LIST_LIMIT)->map(fn (PurchaseOrder $po) => [
                'po_number' => $po->po_number,
                'supplier' => $po->supplier?->name,
                'status' => $po->status->getLabel(),
                'total' => round($po->total(), 2),
                'order_date' => $po->order_date?->toDateString(),
                'expected_date' => $po->expected_date?->toDateString(),
            ])->values()->all(),
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
