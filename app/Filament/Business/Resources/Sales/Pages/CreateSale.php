<?php

namespace App\Filament\Business\Resources\Sales\Pages;

use App\Filament\Business\Resources\Sales\SaleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (filled($data['sale_date'] ?? null)) {
            $data['sale_date'] = Carbon::parse($data['sale_date'])->setTimeFrom(now());
        }

        return $data;
    }
}
