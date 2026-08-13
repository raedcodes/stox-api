<?php

namespace App\Filament\Business\Resources\Products\Pages;

use App\Exports\ImportTemplateExport;
use App\Exports\ProductsCatalogExport;
use App\Filament\Business\Concerns\HandlesExcelImportNotifications;
use App\Filament\Business\Resources\Products\ProductResource;
use App\Imports\ProductsImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ListProducts extends ListRecords
{
    use HandlesExcelImportNotifications;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('exportExcel')
                ->label(__('common.fields.export_excel'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new ProductsCatalogExport($this->getFilteredTableQuery()),
                    'products-catalog.xlsx'
                )),
            Action::make('downloadProductsImportTemplate')
                ->label(__('products.actions.download_import_template'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new ImportTemplateExport(
                        ['Name', 'SKU', 'Barcode', 'Category', 'Brand', 'Unit', 'Cost price', 'Selling price', 'WSP', 'Min stock level', 'Reorder level', 'Active', 'Description'],
                        [['Cough Syrup 100ml', 'SKU-0001', '1234567890123', 'Medicine', 'Acme', 'Piece', 2.5, 4, 3.5, 10, 15, 'Yes', 'Relieves cough and throat irritation.']],
                    ),
                    'products-import-template.xlsx'
                )),
            Action::make('importProducts')
                ->label(__('products.actions.import_excel'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->schema([
                    FileUpload::make('file')
                        ->label(__('common.fields.file'))
                        ->required()
                        ->disk('local')
                        ->directory('imports/'.tenant('id'))
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->maxSize(5120)
                        ->helperText(__('products.import.file_helper')),
                ])
                ->action(function (array $data): void {
                    $path = $data['file'];
                    $import = new ProductsImport();

                    try {
                        Excel::import($import, $path, 'local');
                        $this->notifyImportResult('products.import', $import->importedCount(), $import->failures());
                    } catch (Throwable $e) {
                        $this->notifyImportException('products.import', $e);
                    } finally {
                        Storage::disk('local')->delete($path);
                    }
                }),
        ];
    }
}
