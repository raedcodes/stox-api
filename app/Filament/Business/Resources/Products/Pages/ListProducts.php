<?php

namespace App\Filament\Business\Resources\Products\Pages;

use App\Exports\ProductsCatalogExport;
use App\Filament\Business\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListProducts extends ListRecords
{
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
        ];
    }
}
