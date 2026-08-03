<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CustomerTransactionType: string implements HasColor, HasLabel
{
    case CreditSale = 'credit_sale';
    case Payment = 'payment';
    case Return = 'return';

    public function getLabel(): string
    {
        return match ($this) {
            self::CreditSale => 'Sale on account (owed)',
            self::Payment => 'Payment received',
            self::Return => 'Return (credit)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CreditSale => 'danger',
            self::Payment => 'success',
            self::Return => 'info',
        };
    }

    /**
     * Types that reduce a customer's balance (as opposed to CreditSale, which
     * increases it). A payment and a return are economically different — one
     * is cash received, the other a non-cash credit — but both reduce what's
     * owed the same way.
     */
    public static function reducingTypes(): array
    {
        return [self::Payment, self::Return];
    }

    /**
     * All transaction types selectable for a customer ledger entry, value => label.
     */
    public static function options(): array
    {
        return [
            self::CreditSale->value => self::CreditSale->getLabel(),
            self::Payment->value => self::Payment->getLabel(),
            self::Return->value => self::Return->getLabel(),
        ];
    }
}
