<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\SupplierTransactionType;
use App\Enums\UserRole;
use App\Exports\SupplierStatementExport;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Models\Tenant;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class SupplierStatement extends Page
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 56;

    protected string $view = 'filament.business.pages.supplier-statement';

    public static function getNavigationLabel(): string
    {
        return __('supplier_statement.navigation_label');
    }

    public function getTitle(): string
    {
        return __('supplier_statement.navigation_label');
    }

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'supplier_id' => null,
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
                Select::make('supplier_id')
                    ->label(__('common.fields.supplier'))
                    ->options(fn () => Supplier::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live(),
                DatePicker::make('from')
                    ->label(__('common.fields.from'))
                    ->required()
                    ->live(),
                DatePicker::make('until')
                    ->label(__('common.fields.until'))
                    ->required()
                    ->live(),
            ]);
    }

    protected function supplier(): ?Supplier
    {
        $id = $this->data['supplier_id'] ?? null;

        return $id ? Supplier::find($id) : null;
    }

    public function openingBalance(): float
    {
        $supplier = $this->supplier();

        if (! $supplier || ! ($this->data['from'] ?? null)) {
            return 0;
        }

        $totals = $supplier->transactions()
            ->whereDate('transaction_date', '<', $this->data['from'])
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $reducing = array_sum(array_map(
            fn (SupplierTransactionType $type) => (float) ($totals[$type->value] ?? 0),
            SupplierTransactionType::reducingTypes(),
        ));

        return (float) ($totals[SupplierTransactionType::Charge->value] ?? 0) - $reducing;
    }

    /**
     * @return Collection<int, array{transaction: SupplierTransaction, running_balance: float}>
     */
    public function rows(): Collection
    {
        $supplier = $this->supplier();

        if (! $supplier) {
            return collect();
        }

        $running = $this->openingBalance();

        return $supplier->transactions()
            ->when($this->data['from'] ?? null, fn ($q, $date) => $q->whereDate('transaction_date', '>=', $date))
            ->when($this->data['until'] ?? null, fn ($q, $date) => $q->whereDate('transaction_date', '<=', $date))
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->map(function (SupplierTransaction $transaction) use (&$running) {
                $running += $transaction->type === SupplierTransactionType::Charge
                    ? (float) $transaction->amount
                    : -(float) $transaction->amount;

                return ['transaction' => $transaction, 'running_balance' => $running];
            });
    }

    public function closingBalance(): float
    {
        $rows = $this->rows();

        return $rows->isEmpty() ? $this->openingBalance() : $rows->last()['running_balance'];
    }

    protected function getHeaderActions(): array
    {
        return [
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

    protected function exportPdf(): ?StreamedResponse
    {
        $supplier = $this->supplier();

        if (! $supplier) {
            Notification::make()
                ->title(__('common.notifications.select_supplier_first'))
                ->warning()
                ->send();

            return null;
        }

        $pdf = Pdf::loadView('pdf.reports.supplier-statement', [
            'title' => 'Supplier Statement',
            'subtitle' => $supplier->name.' — '.($this->data['from'] ?? '').' to '.($this->data['until'] ?? ''),
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'supplier' => $supplier,
            'openingBalance' => $this->openingBalance(),
            'rows' => $this->rows(),
            'closingBalance' => $this->closingBalance(),
        ]);

        return response()->streamDownload(fn () => print ($pdf->output()), 'supplier-statement.pdf');
    }

    protected function exportExcel(): mixed
    {
        $supplier = $this->supplier();

        if (! $supplier) {
            Notification::make()
                ->title(__('common.notifications.select_supplier_first'))
                ->warning()
                ->send();

            return null;
        }

        return Excel::download(
            new SupplierStatementExport($supplier, $this->openingBalance(), $this->rows(), $this->closingBalance()),
            'supplier-statement.xlsx'
        );
    }
}
