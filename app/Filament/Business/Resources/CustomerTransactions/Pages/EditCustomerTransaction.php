<?php

namespace App\Filament\Business\Resources\CustomerTransactions\Pages;

use App\Filament\Business\Resources\CustomerTransactions\CustomerTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditCustomerTransaction extends EditRecord
{
    protected static string $resource = CustomerTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['transaction_date'] ?? null)) {
            $data['transaction_date'] = Carbon::parse($data['transaction_date'])->setTimeFrom($this->record->transaction_date);
        }

        return $data;
    }
}
