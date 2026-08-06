<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsCatalogExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected Builder $query) {}

    public function collection(): Collection
    {
        return $this->query->with(['category', 'brand', 'unit'])->get();
    }

    public function headings(): array
    {
        return [
            'Name', 'SKU', 'Barcode', 'Category', 'Brand', 'Unit',
            'Cost price', 'Selling price', 'WSP', 'Min stock', 'Reorder level',
            'Total stock', 'Variants', 'Active',
        ];
    }

    public function map($product): array
    {
        /** @var Product $product */
        return [
            $product->name,
            $product->sku,
            $product->barcode,
            $product->category?->name,
            $product->brand?->name,
            $product->unit?->abbreviation,
            (float) $product->cost_price,
            (float) $product->selling_price,
            (float) $product->wsp,
            $product->min_stock_level !== null ? (float) $product->min_stock_level : null,
            $product->reorder_level !== null ? (float) $product->reorder_level : null,
            (float) ($product->stock_on_hand ?? 0),
            (int) ($product->active_variants_count ?? 1),
            $product->is_active ? 'Yes' : 'No',
        ];
    }
}
