<?php

namespace App\Filament\Business\Resources\SaleReturns\Pages;

use App\Filament\Business\Resources\SaleReturns\SaleReturnResource;
use App\Models\SaleReturn;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSaleReturn extends ViewRecord
{
    protected static string $resource = SaleReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (SaleReturn $record) => $record->processed_at === null),
        ];
    }
}
