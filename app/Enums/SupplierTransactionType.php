<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SupplierTransactionType: string implements HasColor, HasLabel
{
    case Charge = 'charge';
    case Payment = 'payment';
    case Return = 'return';

    public function getLabel(): string
    {
        return match ($this) {
            self::Charge => 'Charge (invoice received)',
            self::Payment => 'Payment made',
            self::Return => 'Return (credit)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Charge => 'danger',
            self::Payment => 'success',
            self::Return => 'info',
        };
    }

    /**
     * Types that reduce what's owed to a supplier (as opposed to Charge,
     * which increases it). A payment and a return are economically
     * different — one is cash paid out, the other a non-cash credit — but
     * both reduce the balance the same way.
     */
    public static function reducingTypes(): array
    {
        return [self::Payment, self::Return];
    }

    /**
     * All transaction types selectable for a supplier ledger entry, value => label.
     */
    public static function options(): array
    {
        return [
            self::Charge->value => self::Charge->getLabel(),
            self::Payment->value => self::Payment->getLabel(),
            self::Return->value => self::Return->getLabel(),
        ];
    }
}
