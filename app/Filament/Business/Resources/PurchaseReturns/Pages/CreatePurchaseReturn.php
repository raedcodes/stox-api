<?php

namespace App\Filament\Business\Resources\PurchaseReturns\Pages;

use App\Filament\Business\Resources\PurchaseReturns\PurchaseReturnResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseReturn extends CreateRecord
{
    protected static string $resource = PurchaseReturnResource::class;
}
