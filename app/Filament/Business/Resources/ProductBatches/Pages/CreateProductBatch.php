<?php

namespace App\Filament\Business\Resources\ProductBatches\Pages;

use App\Filament\Business\Resources\ProductBatches\ProductBatchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductBatch extends CreateRecord
{
    protected static string $resource = ProductBatchResource::class;
}
