<?php

namespace App\Filament\Business\Resources\CustomerTransactions\Pages;

use App\Filament\Business\Resources\CustomerTransactions\CustomerTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerTransaction extends ViewRecord
{
    protected static string $resource = CustomerTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
