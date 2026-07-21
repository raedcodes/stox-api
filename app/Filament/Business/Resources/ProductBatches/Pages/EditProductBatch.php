<?php

namespace App\Filament\Business\Resources\ProductBatches\Pages;

use App\Filament\Business\Resources\ProductBatches\ProductBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductBatch extends EditRecord
{
    protected static string $resource = ProductBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
