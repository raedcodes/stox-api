<?php

namespace App\Filament\Business\Resources\ProductBatches\Pages;

use App\Filament\Business\Resources\ProductBatches\ProductBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductBatches extends ListRecords
{
    protected static string $resource = ProductBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
