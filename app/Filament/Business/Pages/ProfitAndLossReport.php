<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Exports\ProfitAndLossReportExport;
use App\Filament\Business\Concerns\HasStructuredAiSummary;
use App\Models\Expense;
use App\Models\SaleItem;
use App\Models\Tenant;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ProfitAndLossReport extends Page
{
    use HasStructuredAiSummary;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 52;

    protected string $view = 'filament.business.pages.profit-and-loss-report';

    public static function getNavigationLabel(): string
    {
        return __('profit_and_loss_report.navigation_label');
    }

    public function getTitle(): string
    {
        return __('profit_and_loss_report.navigation_label');
    }

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->startOfMonth()->toDateString(),
            'until' => now()->endOfMonth()->toDateString(),
        ]);

        $this->loadCachedAiSummary();
    }

    /**
     * Keeps the displayed summary in sync with whatever's cached for the
     * newly selected range (or clears it) instead of leaving a stale
     * summary from the previous date range on screen.
     */
    public function updated(string $name): void
    {
        if (in_array($name, ['data.from', 'data.until'], true)) {
            $this->loadCachedAiSummary();
        }
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('from')
                    ->required()
                    ->live(),
                DatePicker::make('until')
                    ->required()
                    ->live(),
            ]);
    }

    protected function saleItemsQuery(): Builder
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed)
            ->when($this->data['from'] ?? null, fn ($q, $date) => $q->whereDate('sales.sale_date', '>=', $date))
            ->when($this->data['until'] ?? null, fn ($q, $date) => $q->whereDate('sales.sale_date', '<=', $date))
            ->select('sale_items.*')
            ->with('product');
    }

    public function revenue(): float
    {
        return $this->saleItemsQuery()->get()->sum(fn (SaleItem $item) => $item->total());
    }

    public function cogs(): float
    {
        return $this->saleItemsQuery()->get()->sum(fn (SaleItem $item) => $item->cogsAmount());
    }

    public function grossProfit(): float
    {
        return $this->revenue() - $this->cogs();
    }

    public function grossMarginPercent(): float
    {
        $revenue = $this->revenue();

        return $revenue > 0 ? ($this->grossProfit() / $revenue) * 100 : 0;
    }

    protected function expensesQuery(): Builder
    {
        return Expense::query()
            ->when($this->data['from'] ?? null, fn ($q, $date) => $q->whereDate('expense_date', '>=', $date))
            ->when($this->data['until'] ?? null, fn ($q, $date) => $q->whereDate('expense_date', '<=', $date));
    }

    public function expenses(): float
    {
        return (float) $this->expensesQuery()->sum('amount');
    }

    public function netProfit(): float
    {
        return $this->grossProfit() - $this->expenses();
    }

    /**
     * The same figures broken out per location, so a multi-branch owner can
     * see which location is actually profitable instead of only one
     * combined number. Sorted highest gross profit first.
     *
     * Expenses per location only include expenses directly attributed to
     * that location (via its location_id) — company-wide expenses are
     * excluded here and only appear in the combined totals above, since
     * allocating shared costs across locations isn't a figure this report
     * attempts to compute.
     *
     * @return Collection<int, array{location: ?\App\Models\Location, revenue: float, cogs: float, grossProfit: float, grossMarginPercent: float, expenses: float, netProfit: float}>
     */
    public function byLocation(): Collection
    {
        return $this->saleItemsQuery()
            ->with('sale.location')
            ->get()
            ->groupBy(fn (SaleItem $item) => $item->sale->location_id)
            ->map(function (Collection $items) {
                $revenue = $items->sum(fn (SaleItem $item) => $item->total());
                $cogs = $items->sum(fn (SaleItem $item) => $item->cogsAmount());
                $grossProfit = $revenue - $cogs;
                $locationId = $items->first()->sale->location_id;
                $expenses = $locationId ? (float) $this->expensesQuery()->where('location_id', $locationId)->sum('amount') : 0.0;

                return [
                    'location' => $items->first()->sale->location,
                    'revenue' => $revenue,
                    'cogs' => $cogs,
                    'grossProfit' => $grossProfit,
                    'grossMarginPercent' => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0,
                    'expenses' => $expenses,
                    'netProfit' => $grossProfit - $expenses,
                ];
            })
            ->sortByDesc('grossProfit')
            ->values();
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
                ->action(fn () => $this->exportExcel()),
        ];
    }

    protected function aiSummaryCacheKey(): string
    {
        return 'pl-ai-summary:'.tenant('id').':'.($this->data['from'] ?? '').':'.($this->data['until'] ?? '');
    }

    protected function aiSummarySystemInstruction(): string
    {
        return "You are a financial analyst summarizing a small business's profit & loss figures for a busy owner. Be concise and specific. Use the exact figures given — especially any percentages already computed for you — rather than computing your own.";
    }

    public function netMarginPercent(): float
    {
        $revenue = $this->revenue();

        return $revenue > 0 ? ($this->netProfit() / $revenue) * 100 : 0;
    }

    public function aiSummaryKpis(): array
    {
        $currency = tenant('currency')?->value;

        return [
            ['label' => __('sales_report.kpis.total_revenue'), 'value' => $currency.' '.number_format($this->revenue(), 2)],
            ['label' => __('profit_and_loss_report.kpis.net_profit'), 'value' => $currency.' '.number_format($this->netProfit(), 2)],
            ['label' => __('profit_and_loss_report.kpis.net_margin'), 'value' => number_format($this->netMarginPercent(), 1).'%'],
        ];
    }

    protected function buildAiSummaryPrompt(): string
    {
        $currency = tenant('currency')?->value;
        $byLocation = $this->byLocation();

        $lines = [
            "Period: {$this->data['from']} to {$this->data['until']}",
            "Revenue: {$currency} ".number_format($this->revenue(), 2),
            "Cost of goods sold: {$currency} ".number_format($this->cogs(), 2),
            'Gross profit: '.$currency.' '.number_format($this->grossProfit(), 2).' ('.number_format($this->grossMarginPercent(), 1).'% margin)',
            "Expenses: {$currency} ".number_format($this->expenses(), 2),
            'Net profit: '.$currency.' '.number_format($this->netProfit(), 2).' ('.number_format($this->netMarginPercent(), 1).'% net margin)',
        ];

        if ($byLocation->count() > 1) {
            $lines[] = 'By location (net profit):';

            foreach ($byLocation as $row) {
                $lines[] = '- '.($row['location']?->name ?? 'Unassigned').': '.$currency.' '.number_format($row['netProfit'], 2);
            }
        }

        return implode("\n", $lines);
    }

    protected function exportPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('pdf.reports.profit-and-loss', [
            'title' => 'Profit & Loss (Gross Profit)',
            'subtitle' => ($this->data['from'] ?? '').' to '.($this->data['until'] ?? ''),
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'revenue' => $this->revenue(),
            'cogs' => $this->cogs(),
            'grossProfit' => $this->grossProfit(),
            'grossMarginPercent' => $this->grossMarginPercent(),
            'expenses' => $this->expenses(),
            'netProfit' => $this->netProfit(),
        ]);

        return response()->streamDownload(fn () => print ($pdf->output()), 'profit-and-loss-report.pdf');
    }

    protected function exportExcel(): mixed
    {
        return Excel::download(
            new ProfitAndLossReportExport(
                $this->revenue(),
                $this->cogs(),
                $this->grossProfit(),
                $this->grossMarginPercent(),
                $this->expenses(),
                $this->netProfit(),
            ),
            'profit-and-loss-report.xlsx'
        );
    }
}
