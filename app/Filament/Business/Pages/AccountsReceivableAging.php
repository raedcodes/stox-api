<?php

namespace App\Filament\Business\Pages;

use App\Enums\CustomerTransactionType;
use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Exports\AccountsReceivableAgingExport;
use App\Filament\Business\Concerns\HasStructuredAiSummary;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\Accounting\AgingCalculator;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class AccountsReceivableAging extends Page
{
    use HasStructuredAiSummary;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 56;

    protected string $view = 'filament.business.pages.accounts-receivable-aging';

    public static function getNavigationLabel(): string
    {
        return __('accounts_receivable_aging.navigation_label');
    }

    public function getTitle(): string
    {
        return __('accounts_receivable_aging.navigation_label');
    }

    public function mount(): void
    {
        $this->loadCachedAiSummary();
    }

    /**
     * Every customer with an outstanding balance, aged via AgingCalculator.
     * Customers carry no payment-terms/due-date concept at all (only
     * credit_limit, a cap) — so a credit sale's due date is its own
     * transaction date, i.e. treated as due on receipt.
     *
     * @return Collection<int, array{customer: Customer, buckets: array}>
     */
    public function rows(): Collection
    {
        $asOf = Carbon::now();

        return Customer::query()
            ->with('transactions')
            ->get()
            ->map(function (Customer $customer) use ($asOf) {
                $transactions = $customer->transactions->sortBy([['transaction_date', 'asc'], ['id', 'asc']])->values();

                $buckets = AgingCalculator::bucket(
                    $transactions,
                    CustomerTransactionType::CreditSale,
                    CustomerTransactionType::reducingTypes(),
                    fn ($transaction) => Carbon::parse($transaction->transaction_date),
                    $asOf,
                );

                return ['customer' => $customer, 'buckets' => $buckets];
            })
            ->filter(fn (array $row) => $row['buckets']['total'] > 0.005)
            ->sortByDesc(fn (array $row) => $row['buckets']['total'])
            ->values();
    }

    public function grandTotals(): array
    {
        $totals = array_fill_keys([...AgingCalculator::BUCKETS, 'total'], 0.0);

        foreach ($this->rows() as $row) {
            foreach ($totals as $key => $value) {
                $totals[$key] += $row['buckets'][$key];
            }
        }

        return $totals;
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
                ->action(fn () => Excel::download(new AccountsReceivableAgingExport($this->rows()), 'accounts-receivable-aging.xlsx')),
        ];
    }

    protected function aiSummaryCacheKey(): string
    {
        return 'ar-aging-ai-summary:'.tenant('id').':'.now()->toDateString();
    }

    protected function aiSummarySystemInstruction(): string
    {
        return 'You are a credit-control analyst summarizing an accounts receivable aging snapshot for a busy owner. Be concise and specific. Use the exact figures given rather than computing your own.';
    }

    public function aiSummaryKpis(): array
    {
        $currency = tenant('currency')?->value;
        $totals = $this->grandTotals();

        return [
            ['label' => __('accounts_receivable_aging.kpis.total_outstanding'), 'value' => $currency.' '.number_format($totals['total'], 2)],
            ['label' => __('accounts_receivable_aging.kpis.overdue'), 'value' => $currency.' '.number_format($totals['total'] - $totals['current'], 2)],
            ['label' => __('accounts_receivable_aging.kpis.severely_overdue'), 'value' => $currency.' '.number_format($totals['over_90'], 2)],
        ];
    }

    protected function buildAiSummaryPrompt(): string
    {
        $currency = tenant('currency')?->value;
        $totals = $this->grandTotals();
        $rows = $this->rows();

        $lines = [
            'Accounts receivable aging as of '.now()->toDateString().'.',
            'Total outstanding: '.$currency.' '.number_format($totals['total'], 2),
            'Current: '.$currency.' '.number_format($totals['current'], 2),
            '1-30 days overdue: '.$currency.' '.number_format($totals['1_30'], 2),
            '31-60 days overdue: '.$currency.' '.number_format($totals['31_60'], 2),
            '61-90 days overdue: '.$currency.' '.number_format($totals['61_90'], 2),
            'Over 90 days overdue: '.$currency.' '.number_format($totals['over_90'], 2),
            'Most overdue customers:',
        ];

        foreach ($rows->sortByDesc(fn (array $row) => $row['buckets']['over_90'] + $row['buckets']['61_90'])->take(5) as $row) {
            $lines[] = '- '.$row['customer']->name.': '.$currency.' '.number_format($row['buckets']['total'], 2).' total, '.$currency.' '.number_format($row['buckets']['over_90'], 2).' over 90 days';
        }

        return implode("\n", $lines);
    }

    protected function exportPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('pdf.reports.accounts-receivable-aging', [
            'title' => 'Accounts Receivable Aging',
            'subtitle' => now()->toDateString(),
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'rows' => $this->rows(),
            'totals' => $this->grandTotals(),
        ]);

        return response()->streamDownload(fn () => print ($pdf->output()), 'accounts-receivable-aging.pdf');
    }
}
