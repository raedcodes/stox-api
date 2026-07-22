<?php

namespace App\Filament\Business\Resources\StockAdjustments\Pages;

use App\Filament\Business\Resources\StockAdjustments\StockAdjustmentResource;
use App\Models\StockAdjustment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $quantityChange = $data['direction'] === 'decrease'
            ? -abs((float) $data['quantity'])
            : abs((float) $data['quantity']);

        return DB::transaction(function () use ($data, $quantityChange) {
            $adjustment = StockAdjustment::create([
                'product_id' => $data['product_id'],
                'location_id' => $data['location_id'],
                'product_batch_id' => $data['product_batch_id'],
                'quantity_change' => $quantityChange,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'adjustment_date' => $data['adjustment_date'],
            ]);

            $adjustment->apply();

            return $adjustment;
        });
    }
}
