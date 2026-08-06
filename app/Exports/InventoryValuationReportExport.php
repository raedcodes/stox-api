<?php

namespace App\Exports;

use App\Models\ProductBatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryValuationReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected Builder $query) {}

    /**
     * Not FromQuery: that interface streams via Eloquent's chunk(), which
     * appends its own ORDER BY id — invalid here since id isn't in this
     * query's GROUP BY, and rejected outright under MySQL's
     * only_full_group_by mode. The row count (distinct product/location
     * combinations for one tenant) is always small enough to fetch at once.
     */
    public function collection(): Collection
    {
        return $this->query->get();
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
