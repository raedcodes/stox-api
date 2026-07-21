<?php

namespace App\Filament\Business\Resources\Products\Pages;

use App\Filament\Business\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
