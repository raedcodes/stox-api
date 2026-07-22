<?php

namespace App\Filament\Business\Resources\StockMovements\Pages;

use App\Filament\Business\Resources\StockMovements\StockMovementResource;
use Filament\Resources\Pages\ListRecords;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;
}
