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
        return ['Product', 'Location', 'On hand', 'Unit cost', 'Value'];
    }

    public function map($row): array
    {
        /** @var ProductBatch $row */
        return [
            $row->product->name,
            $row->location->name,
            (float) $row->total_quantity,
            (float) $row->product->cost_price,
            (float) $row->total_value,
        ];
    }
}
