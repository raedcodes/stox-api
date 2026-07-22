<?php

namespace App\Filament\Business\Pages;

use App\Enums\SaleStatus;
use App\Models\SaleItem;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

class ProfitAndLossReport extends Page
{
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

    protected function exportPdf(): Response
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
        ]);

        return $pdf->stream('profit-and-loss-report.pdf');
    }
}
