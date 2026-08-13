<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
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
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class PurchaseReport extends Page implements HasTable
{
    use InteractsWithTable;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 51;

    protected string $view = 'filament.business.pages.purchase-report';

    public static function getNavigationLabel(): string
    {
        return __('purchase_report.navigation_label');
    }

    public function getTitle(): string
    {
        return __('purchase_report.navigation_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => PurchaseOrder::query())
            ->columns([
                TextColumn::make('order_date')
                    ->label(__('common.fields.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('po_number')
                    ->label(__('common.fields.po_number')),
                TextColumn::make('supplier.name')
                    ->label(__('common.fields.supplier'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('common.fields.status'))
                    ->badge(),
                TextColumn::make('total')
                    ->label(__('common.fields.total'))
                    ->state(fn (PurchaseOrder $record) => $record->total())
                    ->money(fn () => tenant('currency')?->value),
            ])
            ->groups([
                Group::make('order_date')
                    ->label(__('common.fields.month'))
                    ->date()
                    ->getTitleFromRecordUsing(fn (PurchaseOrder $record) => $record->order_date->translatedFormat('F Y')),
                Group::make('supplier.name')
                    ->label(__('common.fields.supplier')),
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
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('order_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('order_date', '<=', $date));
                    }),
                SelectFilter::make('supplier_id')
                    ->label(__('common.fields.supplier'))
                    ->options(fn () => Supplier::query()->pluck('name', 'id')),
            ])
            ->defaultSort('order_date', 'desc');
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
                    new PurchaseReportExport($this->getFilteredTableQuery()),
                    'purchase-report.xlsx'
                )),
        ];
    }

    protected function exportPdf(): StreamedResponse
    {
        $orders = $this->getFilteredTableQuery()->with('supplier')->get();

        $pdf = Pdf::loadView('pdf.reports.purchases', [
            'title' => 'Purchase Report',
            'subtitle' => null,
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'orders' => $orders,
        ]);

        return response()->streamDownload(fn () => print ($pdf->output()), 'purchase-report.pdf');
    }
}
