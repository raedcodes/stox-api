<?php

namespace App\Filament\Business\Resources\PurchaseReturns\Pages;

use App\Filament\Business\Resources\PurchaseReturns\PurchaseReturnResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseReturn extends EditRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
