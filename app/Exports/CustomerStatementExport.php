<?php

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerStatementExport implements FromArray, WithHeadings
{
    public function __construct(
        protected Customer $customer,
        protected float $openingBalance,
        protected Collection $rows,
        protected float $closingBalance,
    ) {}

    public function headings(): array
    {
        return ['Date', 'Type', 'Description', 'Amount', 'Balance'];
    }

    public function array(): array
    {
        $rows = [
            ['', '', 'Opening balance', '', $this->openingBalance],
        ];

        foreach ($this->rows as $row) {
            $transaction = $row['transaction'];

            $rows[] = [
                $transaction->transaction_date->format('Y-m-d'),
                $transaction->type->getLabel(),
                $transaction->description,
                (float) $transaction->amount,
                $row['running_balance'],
            ];
        }

        $rows[] = ['', '', 'Closing balance', '', $this->closingBalance];

        return $rows;
    }
}
