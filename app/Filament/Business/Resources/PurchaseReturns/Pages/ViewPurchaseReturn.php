<?php

namespace App\Filament\Business\Resources\PurchaseReturns\Pages;

use App\Filament\Business\Resources\PurchaseReturns\PurchaseReturnResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseReturn extends ViewRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
