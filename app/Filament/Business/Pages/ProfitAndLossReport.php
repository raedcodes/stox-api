<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Exports\ProfitAndLossReportExport;
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
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 52;

    protected string $view = 'filament.business.pages.profit-and-loss-report';

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
        return $this->saleItemsQuery()->get()->sum(fn (SaleItem $item) => (float) $item->quantity * (float) $item->product->cost_price);
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
                $cogs = $items->sum(fn (SaleItem $item) => (float) $item->quantity * (float) $item->product->cost_price);
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
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->action(fn () => $this->exportPdf()),
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->exportExcel()),
        ];
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
