<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Exports\InventoryValuationReportExport;
use App\Models\Location;
use App\Models\ProductBatch;
use App\Models\Tenant;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class InventoryValuationReport extends Page implements HasTable
{
    use InteractsWithTable;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 54;

    protected string $view = 'filament.business.pages.inventory-valuation-report';

    public function getTableRecordKey(Model|array $record): string
    {
        return "{$record->product_id}-{$record->location_id}";
    }

    protected function baseQuery(): Builder
    {
        return ProductBatch::query()
            ->with(['product.brand', 'location'])
            ->where('product_batches.is_active', true)
            ->selectRaw('product_batches.product_id, product_batches.location_id, SUM(product_batches.quantity) as total_quantity, SUM(product_batches.quantity * product_batches.unit_cost) as total_value')
            ->groupBy('product_batches.product_id', 'product_batches.location_id');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->baseQuery())
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.brand.name')
                    ->label('Brand')
                    ->placeholder('—'),
                TextColumn::make('product.description')
                    ->label('Description')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location.name')
                    ->label('Location'),
                TextColumn::make('total_quantity')
                    ->label('On hand'),
                TextColumn::make('unit_cost')
                    ->label('Unit cost (avg)')
                    ->state(fn ($record) => (float) $record->total_quantity > 0
                        ? (float) $record->total_value / (float) $record->total_quantity
                        : 0)
                    ->money(fn () => tenant('currency')?->value)
                    ->tooltip('Weighted average cost across this product\'s batches at this location — batches can have different actual costs (e.g. landed costs vary by shipment).'),
                TextColumn::make('total_value')
                    ->label('Total Value')
                    ->money(fn () => tenant('currency')?->value)
                    ->summarize(Sum::make()->money(fn () => tenant('currency')?->value)),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::query()->pluck('name', 'id')),
            ])
            ->defaultSort('total_value', 'desc')
            ->defaultKeySort(false);
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
                    new InventoryValuationReportExport($this->getFilteredTableQuery()),
                    'inventory-valuation-report.xlsx'
                )),
        ];
    }

    protected function exportPdf(): StreamedResponse
    {
        $rows = $this->getFilteredTableQuery()->with(['product.brand', 'location'])->get();

        $pdf = Pdf::loadView('pdf.reports.inventory-valuation', [
            'title' => 'Inventory Valuation Report',
            'subtitle' => null,
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'rows' => $rows,
        ]);

        return response()->streamDownload(fn () => print ($pdf->output()), 'inventory-valuation-report.pdf');
    }
}
