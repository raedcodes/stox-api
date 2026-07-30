<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\SupplierTransactionType;
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
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class SupplierStatement extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 56;

    protected string $view = 'filament.business.pages.supplier-statement';

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
                    ->label('Supplier')
                    ->options(fn () => Supplier::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live(),
                DatePicker::make('from')
                    ->required()
                    ->live(),
                DatePicker::make('until')
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
                ->label('Export PDF')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->action(fn () => $this->exportPdf()),
        ];
    }

    protected function exportPdf(): ?StreamedResponse
    {
        $supplier = $this->supplier();

        if (! $supplier) {
            Notification::make()
                ->title('Select a supplier first')
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
}
