<?php

namespace App\Filament\Business\Resources\Sales\Pages;

use App\Filament\Business\Resources\Sales\SaleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;
}
