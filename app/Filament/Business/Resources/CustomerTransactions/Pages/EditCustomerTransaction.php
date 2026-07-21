<?php

namespace App\Filament\Business\Resources\CustomerTransactions\Pages;

use App\Filament\Business\Resources\CustomerTransactions\CustomerTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerTransaction extends EditRecord
{
    protected static string $resource = CustomerTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
