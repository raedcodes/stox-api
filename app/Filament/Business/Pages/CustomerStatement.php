<?php

namespace App\Filament\Business\Pages;

use App\Enums\CustomerTransactionType;
use App\Enums\NavigationGroup;
use App\Models\Customer;
use App\Models\CustomerTransaction;
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

class CustomerStatement extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 55;

    protected string $view = 'filament.business.pages.customer-statement';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'customer_id' => null,
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
                Select::make('customer_id')
                    ->label('Customer')
                    ->options(fn () => Customer::query()->pluck('name', 'id'))
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

    protected function customer(): ?Customer
    {
        $id = $this->data['customer_id'] ?? null;

        return $id ? Customer::find($id) : null;
    }

    public function openingBalance(): float
    {
        $customer = $this->customer();

        if (! $customer || ! ($this->data['from'] ?? null)) {
            return 0;
        }

        $totals = $customer->transactions()
            ->whereDate('transaction_date', '<', $this->data['from'])
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return (float) ($totals[CustomerTransactionType::CreditSale->value] ?? 0)
            - (float) ($totals[CustomerTransactionType::Payment->value] ?? 0);
    }

    /**
     * @return Collection<int, array{transaction: CustomerTransaction, running_balance: float}>
     */
    public function rows(): Collection
    {
        $customer = $this->customer();

        if (! $customer) {
            return collect();
        }

        $running = $this->openingBalance();

        return $customer->transactions()
            ->when($this->data['from'] ?? null, fn ($q, $date) => $q->whereDate('transaction_date', '>=', $date))
            ->when($this->data['until'] ?? null, fn ($q, $date) => $q->whereDate('transaction_date', '<=', $date))
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->map(function (CustomerTransaction $transaction) use (&$running) {
                $running += $transaction->type === CustomerTransactionType::CreditSale
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
        $customer = $this->customer();

        if (! $customer) {
            Notification::make()
                ->title('Select a customer first')
                ->warning()
                ->send();

            return null;
        }

        $pdf = Pdf::loadView('pdf.reports.customer-statement', [
            'title' => 'Customer Statement',
            'subtitle' => $customer->name.' — '.($this->data['from'] ?? '').' to '.($this->data['until'] ?? ''),
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'customer' => $customer,
            'openingBalance' => $this->openingBalance(),
            'rows' => $this->rows(),
            'closingBalance' => $this->closingBalance(),
        ]);

        return response()->streamDownload(fn () => print ($pdf->output()), 'customer-statement.pdf');
    }
}
