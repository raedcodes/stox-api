<?php

namespace App\Filament\Business\Resources\CustomerTransactions\Pages;

use App\Filament\Business\Resources\CustomerTransactions\CustomerTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerTransaction extends CreateRecord
{
    protected static string $resource = CustomerTransactionResource::class;
}
