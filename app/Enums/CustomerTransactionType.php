<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CustomerTransactionType: string implements HasColor, HasLabel
{
    case CreditSale = 'credit_sale';
    case Payment = 'payment';

    public function getLabel(): string
    {
        return match ($this) {
            self::CreditSale => 'Credit sale',
            self::Payment => 'Payment received',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CreditSale => 'danger',
            self::Payment => 'success',
        };
    }

    /**
     * All transaction types selectable for a customer ledger entry, value => label.
     */
    public static function options(): array
    {
        return [
            self::CreditSale->value => self::CreditSale->getLabel(),
            self::Payment->value => self::Payment->getLabel(),
        ];
    }
}
