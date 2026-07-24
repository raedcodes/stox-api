<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Exports\PurchaseReportExport;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class PurchaseReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 51;

    protected string $view = 'filament.business.pages.purchase-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => PurchaseOrder::query())
            ->columns([
                TextColumn::make('order_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('po_number')
                    ->label('PO #'),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('total')
                    ->label('Total')
                    ->state(fn (PurchaseOrder $record) => $record->total())
                    ->money(fn () => tenant('currency')?->value),
            ])
            ->groups([
                Group::make('order_date')
                    ->label('Month')
                    ->date()
                    ->getTitleFromRecordUsing(fn (PurchaseOrder $record) => $record->order_date->format('F Y')),
                Group::make('supplier.name')
                    ->label('Supplier'),
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
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('order_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('order_date', '<=', $date));
                    }),
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->options(fn () => Supplier::query()->pluck('name', 'id')),
            ])
            ->defaultSort('order_date', 'desc');
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
                    new PurchaseReportExport($this->getFilteredTableQuery()),
                    'purchase-report.xlsx'
                )),
        ];
    }

    protected function exportPdf(): Response
    {
        $orders = $this->getFilteredTableQuery()->with('supplier')->get();

        $pdf = Pdf::loadView('pdf.reports.purchases', [
            'title' => 'Purchase Report',
            'subtitle' => null,
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'orders' => $orders,
        ]);

        return $pdf->stream('purchase-report.pdf');
    }
}
