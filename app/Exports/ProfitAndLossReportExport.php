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
        protected float $expenses,
        protected float $netProfit,
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
            ['Expenses', -$this->expenses],
            ['Net profit', $this->netProfit],
        ];
    }
}
