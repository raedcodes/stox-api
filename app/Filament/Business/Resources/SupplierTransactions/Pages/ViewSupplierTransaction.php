<?php

namespace App\Filament\Business\Resources\SupplierTransactions\Pages;

use App\Filament\Business\Resources\SupplierTransactions\SupplierTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplierTransaction extends ViewRecord
{
    protected static string $resource = SupplierTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
