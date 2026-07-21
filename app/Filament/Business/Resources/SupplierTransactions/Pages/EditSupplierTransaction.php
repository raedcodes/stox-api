<?php

namespace App\Filament\Business\Resources\SupplierTransactions\Pages;

use App\Filament\Business\Resources\SupplierTransactions\SupplierTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplierTransaction extends EditRecord
{
    protected static string $resource = SupplierTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
