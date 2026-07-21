<?php

namespace App;

enum CustomerTransactionType
{
    const CreditSale = 'credit_sale';
    const Payment = 'payment';

    /**
     * All transaction types selectable for a customer ledger entry, value => label.
     */
    public static function options(): array
    {
        return [
            self::CreditSale => 'Credit sale',
            self::Payment => 'Payment received',
        ];
    }
}
