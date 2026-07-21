<?php

namespace App;

enum SupplierTransactionType
{
    const Charge = 'charge';
    const Payment = 'payment';

    /**
     * All transaction types selectable for a supplier ledger entry, value => label.
     */
    public static function options(): array
    {
        return [
            self::Charge => 'Charge (invoice received)',
            self::Payment => 'Payment made',
        ];
    }
}
