<?php

namespace App\Filament\Business\Resources\Sales\Pages;

use App\Enums\SaleStatus;
use App\Filament\Business\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (Sale $record) => $record->status === SaleStatus::Draft),
        ];
    }
}
