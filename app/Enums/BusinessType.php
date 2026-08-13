<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BusinessType: string implements HasLabel
{
    case Retail = 'retail';
    case Restaurant = 'restaurant';
    case Pharmacy = 'pharmacy';
    case Wholesale = 'wholesale';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('enums.business_type.'.$this->value);
    }

    /**
     * All business types selectable during tenant onboarding, value => label.
     */
    public static function options(): array
    {
        return [
            self::Retail->value => self::Retail->getLabel(),
            self::Restaurant->value => self::Restaurant->getLabel(),
            self::Pharmacy->value => self::Pharmacy->getLabel(),
            self::Wholesale->value => self::Wholesale->getLabel(),
            self::Other->value => self::Other->getLabel(),
        ];
    }
}
