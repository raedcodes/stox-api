<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Exports\SalesReportExport;
use App\Filament\Business\Concerns\HasStructuredAiSummary;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class SalesReport extends Page implements HasTable
{
    use HasStructuredAiSummary;
    use InteractsWithTable;

    public function mount(): void
    {
        $this->loadCachedAiSummary();
    }

    /**
     * Keeps the displayed summary in sync with the currently applied
     * filters instead of leaving a stale summary from a different filter
     * set on screen.
     */
    public function updatedTableFilters(): void
    {
        $this->loadCachedAiSummary();
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.business.pages.sales-report';

    public static function getNavigationLabel(): string
    {
        return __('sales_report.navigation_label');
    }

    public function getTitle(): string
    {
        return __('sales_report.navigation_label');
    }

    protected function baseQuery(): Builder
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed)
            ->select('sale_items.*');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->baseQuery())
            ->columns([
                TextColumn::make('sale.sale_date')
                    ->label(__('common.fields.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('sale.sale_number')
                    ->label(__('common.fields.invoice_number')),
                TextColumn::make('product.name')
                    ->label(__('common.fields.product'))
                    ->searchable(),
                TextColumn::make('product.sku')
                    ->label(__('common.fields.sku'))
                    ->searchable(),
                TextColumn::make('product.brand.name')
                    ->label(__('common.fields.brand'))
                    ->placeholder('—'),
                TextColumn::make('product.category.name')
                    ->label(__('common.fields.category'))
                    ->placeholder('—'),
                TextColumn::make('sale.cashier.name')
                    ->label(__('common.fields.cashier'))
                    ->placeholder('—'),
                TextColumn::make('sale.location.name')
                    ->label(__('common.fields.location'))
                    ->placeholder('—'),
                TextColumn::make('quantity')
                    ->label(__('common.fields.quantity')),
                TextColumn::make('total')
                    ->label(__('common.fields.line_total'))
                    ->state(fn (SaleItem $record) => $record->total())
                    ->money(fn () => tenant('currency')?->value),
            ])
            ->groups([
                Group::make('sale.sale_date')
                    ->label(__('common.fields.date'))
                    ->date(),
                Group::make('product.name')
                    ->label(__('common.fields.product')),
                Group::make('product.category.name')
                    ->label(__('common.fields.category')),
                Group::make('sale.cashier.name')
                    ->label(__('common.fields.cashier')),
                Group::make('sale.location.name')
                    ->label(__('common.fields.location')),
            ])
            ->filters([
                Filter::make('date_range')
                    ->label(__('common.fields.date_range'))
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('sales.sale_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('sales.sale_date', '<=', $date));
                    }),
                SelectFilter::make('product_id')
                    ->label(__('common.fields.product'))
                    ->options(fn () => Product::query()->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label(__('common.fields.location'))
                    ->options(fn () => Location::query()->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $locationId) => $q->where('sales.location_id', $locationId)
                    )),
                SelectFilter::make('category_id')
                    ->label(__('common.fields.category'))
                    ->options(fn () => Category::query()->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $categoryId) => $q->whereHas('product', fn ($q) => $q->where('category_id', $categoryId))
                    )),
                SelectFilter::make('created_by')
                    ->label(__('common.fields.cashier'))
                    // created_by is polymorphic (a staff member or the central
                    // Owner) — a plain id can collide between the two tables,
                    // so options/filtering both carry the type alongside it.
                    ->options(fn () => Staff::query()->pluck('name', 'id')
                        ->mapWithKeys(fn ($name, $id) => ["staff:{$id}" => $name])
                        ->union(
                            User::query()->where('tenant_id', tenant('id'))->pluck('name', 'id')
                                ->mapWithKeys(fn ($name, $id) => ["user:{$id}" => $name])
                        ))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        function (Builder $q, string $value) {
                            [$type, $id] = explode(':', $value, 2);

                            $q->where('sales.created_by', $id)
                                ->where('sales.creator_type', $type === 'staff' ? Staff::class : User::class);
                        }
                    )),
            ])
            ->defaultSort('sale.sale_date', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateAiSummary')
                ->label(fn () => $this->aiSummary ? __('common.actions.regenerate_ai_summary') : __('common.actions.generate_ai_summary'))
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->action(fn () => $this->generateAiSummary()),
            Action::make('exportPdf')
                ->label(__('common.actions.export_pdf'))
                ->icon('heroicon-o-document')
                ->color('gray')
                ->action(fn () => $this->exportPdf()),
            Action::make('exportExcel')
                ->label(__('common.fields.export_excel'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => Excel::download(
                    new SalesReportExport($this->getFilteredTableQuery()),
                    'sales-report.xlsx'
                )),
        ];
    }

    protected function aiSummaryCacheKey(): string
    {
        return 'sales-report-ai-summary:'.tenant('id').':'.md5(json_encode($this->tableFilters ?? []));
    }

    protected function aiSummarySystemInstruction(): string
    {
        return 'You are a retail analyst summarizing a filtered sales report for a busy owner. Be concise and specific. Use the exact figures given — especially any percentages or averages already computed for you — rather than computing your own.';
    }

    /**
     * Shared by aiSummaryKpis() (every render) and buildAiSummaryPrompt()
     * (only when generating) so both work from identical figures without
     * duplicating the aggregation logic.
     *
     * @return array{items: \Illuminate\Support\Collection<int, SaleItem>, revenue: float, salesCount: int, totalQuantity: float, averageOrderValue: float, topProducts: \Illuminate\Support\Collection<int, array{name: string, revenue: float, quantity: float}>}
     */
    protected function aiSummaryFigures(): array
    {
        $items = $this->getFilteredTableQuery()->with('product')->get();

        $revenue = $items->sum(fn (SaleItem $item) => $item->total());
        $salesCount = $items->pluck('sale_id')->unique()->count();
        $totalQuantity = (float) $items->sum('quantity');
        $averageOrderValue = $salesCount > 0 ? $revenue / $salesCount : 0.0;

        $topProducts = $items->groupBy('product_id')
            ->map(fn ($group) => [
                'name' => $group->first()->product?->name ?? 'Unknown product',
                'revenue' => $group->sum(fn (SaleItem $item) => $item->total()),
                'quantity' => (float) $group->sum('quantity'),
            ])
            ->sortByDesc('revenue')
            ->take(5)
            ->values();

        return compact('items', 'revenue', 'salesCount', 'totalQuantity', 'averageOrderValue', 'topProducts');
    }

    public function aiSummaryKpis(): array
    {
        $currency = tenant('currency')?->value;
        $figures = $this->aiSummaryFigures();

        return [
            ['label' => __('sales_report.kpis.total_revenue'), 'value' => $currency.' '.number_format($figures['revenue'], 2)],
            ['label' => __('sales_report.kpis.total_sales'), 'value' => number_format($figures['salesCount'])],
            ['label' => __('sales_report.kpis.total_items_sold'), 'value' => number_format($figures['totalQuantity'])],
        ];
    }

    protected function buildAiSummaryPrompt(): string
    {
        $currency = tenant('currency')?->value;
        $figures = $this->aiSummaryFigures();

        $lines = [
            "Filtered sales report — {$figures['salesCount']} sales, {$figures['items']->count()} line items, ".number_format($figures['totalQuantity'])." total units sold.",
            "Total revenue: {$currency} ".number_format($figures['revenue'], 2),
            "Average order value: {$currency} ".number_format($figures['averageOrderValue'], 2),
            'Top products by revenue:',
        ];

        foreach ($figures['topProducts'] as $product) {
            $lines[] = '- '.$product['name'].': '.$currency.' '.number_format($product['revenue'], 2).' ('.number_format($product['quantity'], 0).' units)';
        }

        $topRevenue = $figures['topProducts']->sum('revenue');

        if ($figures['revenue'] > 0 && $figures['topProducts']->isNotEmpty()) {
            $topPercent = ($topRevenue / $figures['revenue']) * 100;
            $lines[] = 'Top '.$figures['topProducts']->count()." products by revenue generated {$currency} ".number_format($topRevenue, 2).' ('.number_format($topPercent, 1).'%) of total revenue.';
        }

        return implode("\n", $lines);
    }

    protected function exportPdf(): StreamedResponse
    {
        $items = $this->getFilteredTableQuery()->with(['product.category', 'product.brand', 'sale.cashier', 'sale.location'])->get();

        $pdf = Pdf::loadView('pdf.reports.sales', [
            'title' => 'Sales Report',
            'subtitle' => null,
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'items' => $items,
        ]);

        return response()->streamDownload(fn () => print ($pdf->output()), 'sales-report.pdf');
    }
}
