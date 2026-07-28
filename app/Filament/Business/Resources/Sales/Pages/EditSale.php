<?php

namespace App\Filament\Business\Resources\Sales\Pages;

use App\Filament\Business\Resources\Sales\SaleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['sale_date'] ?? null)) {
            $data['sale_date'] = Carbon::parse($data['sale_date'])->setTimeFrom($this->record->sale_date);
        }

        return $data;
    }
}
