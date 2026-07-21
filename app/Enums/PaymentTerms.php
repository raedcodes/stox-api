<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentTerms: string implements HasLabel
{
    case DueOnReceipt = 'due_on_receipt';
    case Cod = 'cod';
    case Prepaid = 'prepaid';
    case Net15 = 'net_15';
    case Net30 = 'net_30';
    case Net45 = 'net_45';
    case Net60 = 'net_60';

    public function getLabel(): string
    {
        return match ($this) {
            self::DueOnReceipt => 'Due on receipt',
            self::Cod => 'Cash on delivery',
            self::Prepaid => 'Prepaid',
            self::Net15 => 'Net 15',
            self::Net30 => 'Net 30',
            self::Net45 => 'Net 45',
            self::Net60 => 'Net 60',
        };
    }

    /**
     * All payment terms selectable for a supplier, value => label.
     */
    public static function options(): array
    {
        return [
            self::DueOnReceipt->value => self::DueOnReceipt->getLabel(),
            self::Cod->value => self::Cod->getLabel(),
            self::Prepaid->value => self::Prepaid->getLabel(),
            self::Net15->value => self::Net15->getLabel(),
            self::Net30->value => self::Net30->getLabel(),
            self::Net45->value => self::Net45->getLabel(),
            self::Net60->value => self::Net60->getLabel(),
        ];
    }
}
