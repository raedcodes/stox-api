<?php

namespace App\Filament\Business\Resources\SupplierTransactions\Pages;

use App\Filament\Business\Resources\SupplierTransactions\SupplierTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplierTransaction extends CreateRecord
{
    protected static string $resource = SupplierTransactionResource::class;
}
