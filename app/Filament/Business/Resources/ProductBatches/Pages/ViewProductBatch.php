<?php

namespace App\Filament\Business\Resources\ProductBatches\Pages;

use App\Filament\Business\Resources\ProductBatches\ProductBatchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProductBatch extends ViewRecord
{
    protected static string $resource = ProductBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
