<?php

namespace App\Exports;

use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesReportExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected Builder $query) {}

    public function query(): Builder
    {
        return $this->query->with(['product.brand', 'product.category', 'sale.cashier']);
    }

    public function headings(): array
    {
        return ['Date', 'Invoice #', 'Product', 'SKU', 'Brand', 'Category', 'Cashier', 'Quantity', 'Line total'];
    }

    public function map($saleItem): array
    {
        /** @var SaleItem $saleItem */
        return [
            $saleItem->sale->sale_date->format('Y-m-d'),
            $saleItem->sale->sale_number,
            $saleItem->product->name,
            $saleItem->product->sku,
            $saleItem->product->brand?->name,
            $saleItem->product->category?->name,
            $saleItem->sale->cashier?->name,
            (float) $saleItem->quantity,
            $saleItem->total(),
        ];
    }
}
