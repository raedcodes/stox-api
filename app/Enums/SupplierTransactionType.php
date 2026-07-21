<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SupplierTransactionType: string implements HasColor, HasLabel
{
    case Charge = 'charge';
    case Payment = 'payment';

    public function getLabel(): string
    {
        return match ($this) {
            self::Charge => 'Charge (invoice received)',
            self::Payment => 'Payment made',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Charge => 'danger',
            self::Payment => 'success',
        };
    }

    /**
     * All transaction types selectable for a supplier ledger entry, value => label.
     */
    public static function options(): array
    {
        return [
            self::Charge->value => self::Charge->getLabel(),
            self::Payment->value => self::Payment->getLabel(),
        ];
    }
}
