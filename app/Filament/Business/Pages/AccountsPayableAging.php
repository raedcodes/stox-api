<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\PaymentTerms;
use App\Enums\SupplierTransactionType;
use App\Enums\UserRole;
use App\Exports\AccountsPayableAgingExport;
use App\Filament\Business\Concerns\HasStructuredAiSummary;
use App\Models\Supplier;
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

class AccountsPayableAging extends Page
{
    use HasStructuredAiSummary;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 57;

    protected string $view = 'filament.business.pages.accounts-payable-aging';

    public static function getNavigationLabel(): string
    {
        return __('accounts_payable_aging.navigation_label');
    }

    public function getTitle(): string
    {
        return __('accounts_payable_aging.navigation_label');
    }

    public function mount(): void
    {
        $this->loadCachedAiSummary();
    }

    /**
     * Days added to a charge's transaction_date to get its due date, per
     * the supplier's own payment_terms — the PaymentTerms enum existed
     * before this report but nothing ever actually consulted it to compute
     * a due date until now.
     */
    protected function netDays(?PaymentTerms $terms): int
    {
        return match ($terms) {
            PaymentTerms::Net15 => 15,
            PaymentTerms::Net30 => 30,
            PaymentTerms::Net45 => 45,
            PaymentTerms::Net60 => 60,
            PaymentTerms::Cod, PaymentTerms::Prepaid, PaymentTerms::DueOnReceipt, null => 0,
        };
    }

    /**
     * @return Collection<int, array{supplier: Supplier, buckets: array}>
     */
    public function rows(): Collection
    {
        $asOf = Carbon::now();

        return Supplier::query()
            ->with('transactions')
            ->get()
            ->map(function (Supplier $supplier) use ($asOf) {
                $transactions = $supplier->transactions->sortBy([['transaction_date', 'asc'], ['id', 'asc']])->values();
                $netDays = $this->netDays($supplier->payment_terms);

                $buckets = AgingCalculator::bucket(
                    $transactions,
                    SupplierTransactionType::Charge,
                    SupplierTransactionType::reducingTypes(),
                    fn ($transaction) => Carbon::parse($transaction->transaction_date)->addDays($netDays),
                    $asOf,
                );

                return ['supplier' => $supplier, 'buckets' => $buckets];
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
                ->action(fn () => Excel::download(new AccountsPayableAgingExport($this->rows()), 'accounts-payable-aging.xlsx')),
        ];
    }

    protected function aiSummaryCacheKey(): string
    {
        return 'ap-aging-ai-summary:'.tenant('id').':'.now()->toDateString();
    }

    protected function aiSummarySystemInstruction(): string
    {
        return 'You are a credit-control analyst summarizing an accounts payable aging snapshot for a busy owner. Be concise and specific. Use the exact figures given rather than computing your own.';
    }

    public function aiSummaryKpis(): array
    {
        $currency = tenant('currency')?->value;
        $totals = $this->grandTotals();

        return [
            ['label' => __('accounts_payable_aging.kpis.total_outstanding'), 'value' => $currency.' '.number_format($totals['total'], 2)],
            ['label' => __('accounts_payable_aging.kpis.overdue'), 'value' => $currency.' '.number_format($totals['total'] - $totals['current'], 2)],
            ['label' => __('accounts_payable_aging.kpis.severely_overdue'), 'value' => $currency.' '.number_format($totals['over_90'], 2)],
        ];
    }

    protected function buildAiSummaryPrompt(): string
    {
        $currency = tenant('currency')?->value;
        $totals = $this->grandTotals();
        $rows = $this->rows();

        $lines = [
            'Accounts payable aging as of '.now()->toDateString().'.',
            'Total outstanding: '.$currency.' '.number_format($totals['total'], 2),
            'Current: '.$currency.' '.number_format($totals['current'], 2),
            '1-30 days overdue: '.$currency.' '.number_format($totals['1_30'], 2),
            '31-60 days overdue: '.$currency.' '.number_format($totals['31_60'], 2),
            '61-90 days overdue: '.$currency.' '.number_format($totals['61_90'], 2),
            'Over 90 days overdue: '.$currency.' '.number_format($totals['over_90'], 2),
            'Most overdue suppliers:',
        ];

        foreach ($rows->sortByDesc(fn (array $row) => $row['buckets']['over_90'] + $row['buckets']['61_90'])->take(5) as $row) {
            $lines[] = '- '.$row['supplier']->name.': '.$currency.' '.number_format($row['buckets']['total'], 2).' total, '.$currency.' '.number_format($row['buckets']['over_90'], 2).' over 90 days';
        }

        return implode("\n", $lines);
    }

    protected function exportPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('pdf.reports.accounts-payable-aging', [
            'title' => 'Accounts Payable Aging',
            'subtitle' => now()->toDateString(),
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'rows' => $this->rows(),
            'totals' => $this->grandTotals(),
        ]);

        return response()->streamDownload(fn () => print ($pdf->output()), 'accounts-payable-aging.pdf');
    }
}
