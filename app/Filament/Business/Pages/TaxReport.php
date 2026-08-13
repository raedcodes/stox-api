<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
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
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class TaxReport extends Page implements HasTable
{
    use InteractsWithTable;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 53;

    protected string $view = 'filament.business.pages.tax-report';

    public static function getNavigationLabel(): string
    {
        return __('tax_report.navigation_label');
    }

    public function getTitle(): string
    {
        return __('tax_report.navigation_label');
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
                    ->label(__('common.fields.product')),
                TextColumn::make('product.sku')
                    ->label(__('common.fields.sku')),
                TextColumn::make('product.brand.name')
                    ->label(__('common.fields.brand'))
                    ->placeholder('—'),
                TextColumn::make('subtotal')
                    ->label(__('tax_report.fields.taxable_subtotal'))
                    ->state(fn (SaleItem $record) => $record->subtotal())
                    ->money(fn () => tenant('currency')?->value),
                TextColumn::make('taxAmount')
                    ->label(__('tax_report.fields.tax_collected'))
                    ->state(fn (SaleItem $record) => $record->taxAmount())
                    ->money(fn () => tenant('currency')?->value),
            ])
            ->groups([
                Group::make('sale.sale_date')
                    ->label(__('common.fields.month'))
                    ->getKeyFromRecordUsing(fn (SaleItem $record) => $record->sale->sale_date->format('Y-m'))
                    ->getTitleFromRecordUsing(fn (SaleItem $record) => $record->sale->sale_date->translatedFormat('F Y')),
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
            ])
            ->defaultSort('sale.sale_date', 'desc');
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
                ->action(fn () => Excel::download(
                    new TaxReportExport($this->getFilteredTableQuery()),
                    'tax-report.xlsx'
                )),
        ];
    }

    protected function exportPdf(): StreamedResponse
    {
        $items = $this->getFilteredTableQuery()->with(['product.brand', 'sale'])->get();

        $pdf = Pdf::loadView('pdf.reports.tax', [
            'title' => 'Tax Report',
            'subtitle' => 'Tax rate: '.((float) TaxSetting::current()->rate).'%',
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'items' => $items,
        ]);

        return response()->streamDownload(fn () => print ($pdf->output()), 'tax-report.pdf');
    }
}
