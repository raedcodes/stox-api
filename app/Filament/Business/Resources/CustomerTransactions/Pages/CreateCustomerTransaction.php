<?php

namespace App\Filament\Business\Resources\CustomerTransactions\Pages;

use App\Filament\Business\Resources\CustomerTransactions\CustomerTransactionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateCustomerTransaction extends CreateRecord
{
    protected static string $resource = CustomerTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (filled($data['transaction_date'] ?? null)) {
            $data['transaction_date'] = Carbon::parse($data['transaction_date'])->setTimeFrom(now());
        }

        return $data;
    }
}
