<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\SaleStatus;
use App\Exports\TaxReportExport;
use App\Models\SaleItem;
use App\Models\TaxSetting;
use App\Models\Tenant;
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
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class TaxReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 53;

    protected string $view = 'filament.business.pages.tax-report';

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
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('sale.sale_number')
                    ->label('Invoice #'),
                TextColumn::make('product.name')
                    ->label('Product'),
                TextColumn::make('subtotal')
                    ->label('Taxable subtotal')
                    ->state(fn (SaleItem $record) => $record->subtotal())
                    ->money(fn () => tenant('currency')?->value),
                TextColumn::make('taxAmount')
                    ->label('Tax collected')
                    ->state(fn (SaleItem $record) => $record->taxAmount())
                    ->money(fn () => tenant('currency')?->value),
            ])
            ->groups([
                Group::make('sale.sale_date')
                    ->label('Month')
                    ->getTitleFromRecordUsing(fn (SaleItem $record) => $record->sale->sale_date->format('F Y')),
            ])
            ->filters([
                Filter::make('date_range')
                    ->label('Date range')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('sales.sale_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('sales.sale_date', '<=', $date));
                    }),
            ])
            ->defaultSort('sale.sale_date', 'desc');
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
                ->action(fn () => Excel::download(
                    new TaxReportExport($this->getFilteredTableQuery()),
                    'tax-report.xlsx'
                )),
        ];
    }

    protected function exportPdf(): Response
    {
        $items = $this->getFilteredTableQuery()->with(['product', 'sale'])->get();

        $pdf = Pdf::loadView('pdf.reports.tax', [
            'title' => 'Tax Report',
            'subtitle' => 'Tax rate: '.((float) TaxSetting::current()->rate).'%',
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'items' => $items,
        ]);

        return $pdf->stream('tax-report.pdf');
    }
}
