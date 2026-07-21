<?php

namespace App\Filament\Business\Resources\SaleReturns\Pages;

use App\Filament\Business\Resources\SaleReturns\SaleReturnResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSaleReturn extends EditRecord
{
    protected static string $resource = SaleReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
