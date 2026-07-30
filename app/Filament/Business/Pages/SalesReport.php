<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\SaleStatus;
use App\Exports\SalesReportExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Tenant;
use App\Models\User;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class SalesReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.business.pages.sales-report';

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
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('product.category.name')
                    ->label('Category')
                    ->placeholder('—'),
                TextColumn::make('sale.cashier.name')
                    ->label('Cashier')
                    ->placeholder('—'),
                TextColumn::make('quantity'),
                TextColumn::make('total')
                    ->label('Line total')
                    ->state(fn (SaleItem $record) => $record->total())
                    ->money(fn () => tenant('currency')?->value),
            ])
            ->groups([
                Group::make('sale.sale_date')
                    ->label('Date')
                    ->date(),
                Group::make('product.name')
                    ->label('Product'),
                Group::make('product.category.name')
                    ->label('Category'),
                Group::make('sale.cashier.name')
                    ->label('Cashier'),
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
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(fn () => Product::query()->pluck('name', 'id')),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::query()->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $categoryId) => $q->whereHas('product', fn ($q) => $q->where('category_id', $categoryId))
                    )),
                SelectFilter::make('created_by')
                    ->label('Cashier')
                    ->options(fn () => User::query()->where('tenant_id', tenant('id'))->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $userId) => $q->where('sales.created_by', $userId)
                    )),
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
                    new SalesReportExport($this->getFilteredTableQuery()),
                    'sales-report.xlsx'
                )),
        ];
    }

    protected function exportPdf(): StreamedResponse
    {
        $items = $this->getFilteredTableQuery()->with(['product.category', 'sale.cashier'])->get();

        $pdf = Pdf::loadView('pdf.reports.sales', [
            'title' => 'Sales Report',
            'subtitle' => null,
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'items' => $items,
        ]);

        return response()->streamDownload(fn () => print ($pdf->output()), 'sales-report.pdf');
    }
}
