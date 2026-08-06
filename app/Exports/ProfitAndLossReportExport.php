<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProfitAndLossReportExport implements FromArray, WithHeadings
{
    public function __construct(
        protected float $revenue,
        protected float $cogs,
        protected float $grossProfit,
        protected float $grossMarginPercent,
    ) {}

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function array(): array
    {
        return [
            ['Revenue', $this->revenue],
            ['Cost of goods sold', -$this->cogs],
            ['Gross profit', $this->grossProfit],
            ['Gross margin %', round($this->grossMarginPercent, 1)],
        ];
    }
}
