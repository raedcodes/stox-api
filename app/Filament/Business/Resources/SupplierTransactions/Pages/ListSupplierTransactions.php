<?php

namespace App\Filament\Business\Resources\SupplierTransactions\Pages;

use App\Filament\Business\Resources\SupplierTransactions\SupplierTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierTransactions extends ListRecords
{
    protected static string $resource = SupplierTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
