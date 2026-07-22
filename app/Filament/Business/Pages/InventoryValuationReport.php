<?php

namespace App\Filament\Business\Pages;

use App\Exports\InventoryValuationReportExport;
use App\Models\Location;
use App\Models\ProductBatch;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;

class InventoryValuationReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.business.pages.inventory-valuation-report';

    protected function baseQuery(): Builder
    {
        return ProductBatch::query()
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->where('product_batches.is_active', true)
            ->selectRaw('product_batches.product_id, product_batches.location_id, SUM(product_batches.quantity) as total_quantity, SUM(product_batches.quantity * products.cost_price) as total_value')
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
                TextColumn::make('location.name')
                    ->label('Location'),
                TextColumn::make('total_quantity')
                    ->label('On hand'),
                TextColumn::make('product.cost_price')
                    ->label('Unit cost')
                    ->money(fn () => tenant('currency')?->value),
                TextColumn::make('total_value')
                    ->label('Value')
                    ->money(fn () => tenant('currency')?->value)
                    ->summarize(Sum::make()->money(fn () => tenant('currency')?->value)),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::query()->pluck('name', 'id')),
            ])
            ->defaultSort('total_value', 'desc');
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

    protected function exportPdf(): Response
    {
        $rows = $this->getFilteredTableQuery()->with(['product', 'location'])->get();

        $pdf = Pdf::loadView('pdf.reports.inventory-valuation', [
            'title' => 'Inventory Valuation Report',
            'subtitle' => null,
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'rows' => $rows,
        ]);

        return $pdf->stream('inventory-valuation-report.pdf');
    }
}
