<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CustomerTransactionType: string implements HasColor, HasLabel
{
    case CreditSale = 'credit_sale';
    case Payment = 'payment';
    case Return = 'return';
    case SaleCancelled = 'sale_cancelled';

    public function getLabel(): string
    {
        return __('enums.customer_transaction_type.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CreditSale => 'danger',
            self::Payment => 'success',
            self::Return => 'info',
            self::SaleCancelled => 'gray',
        };
    }

    /**
     * Types that reduce a customer's balance (as opposed to CreditSale, which
     * increases it). A payment, a return, and a cancelled sale are all
     * economically different — one is cash received, one a non-cash credit
     * for goods that came back, one erasing a sale that shouldn't have
     * counted at all — but all three reduce what's owed the same way.
     */
    public static function reducingTypes(): array
    {
        return [self::Payment, self::Return, self::SaleCancelled];
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
            self::SaleCancelled->value => self::SaleCancelled->getLabel(),
        ];
    }
}
