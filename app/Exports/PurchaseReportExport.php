<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseReportExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Date', 'PO #', 'Supplier', 'Status', 'Total'];
    }

    public function map($order): array
    {
        /** @var PurchaseOrder $order */
        return [
            $order->order_date->format('Y-m-d'),
            $order->po_number,
            $order->supplier->name,
            $order->status->getLabel(),
            $order->total(),
        ];
    }
}
