<?php

namespace App;

enum PaymentTerms
{
    const DueOnReceipt = 'due_on_receipt';
    const Cod = 'cod';
    const Prepaid = 'prepaid';
    const Net15 = 'net_15';
    const Net30 = 'net_30';
    const Net45 = 'net_45';
    const Net60 = 'net_60';

    /**
     * All payment terms selectable for a supplier, value => label.
     */
    public static function options(): array
    {
        return [
            self::DueOnReceipt => 'Due on receipt',
            self::Cod => 'Cash on delivery',
            self::Prepaid => 'Prepaid',
            self::Net15 => 'Net 15',
            self::Net30 => 'Net 30',
            self::Net45 => 'Net 45',
            self::Net60 => 'Net 60',
        ];
    }
}
