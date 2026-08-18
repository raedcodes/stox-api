<?php

namespace App\Exports;

use App\Models\Supplier;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AccountsPayableAgingExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, array{supplier: Supplier, buckets: array}>  $rows
     */
    public function __construct(protected Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Supplier', 'Current', '1-30 days', '31-60 days', '61-90 days', 'Over 90 days', 'Total'];
    }

    public function map($row): array
    {
        return [
            $row['supplier']->name,
            $row['buckets']['current'],
            $row['buckets']['1_30'],
            $row['buckets']['31_60'],
            $row['buckets']['61_90'],
            $row['buckets']['over_90'],
            $row['buckets']['total'],
        ];
    }
}
