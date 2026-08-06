<?php

namespace App\Exports;

use App\Models\ProductBatch;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryValuationReportExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Product', 'Description', 'Location', 'On hand', 'Unit cost (avg)', 'Total Value'];
    }

    public function map($row): array
    {
        /** @var ProductBatch $row */
        $quantity = (float) $row->total_quantity;
        $value = (float) $row->total_value;

        return [
            $row->product->name,
            $row->product->description,
            $row->location->name,
            $quantity,
            $quantity > 0 ? $value / $quantity : 0,
            $value,
        ];
    }
}
