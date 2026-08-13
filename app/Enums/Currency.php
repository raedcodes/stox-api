<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Currency: string implements HasLabel
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case AED = 'AED';
    case SAR = 'SAR';
    case EGP = 'EGP';
    case JOD = 'JOD';
    case LBP = 'LBP';
    case CAD = 'CAD';
    case AUD = 'AUD';

    public function getLabel(): string
    {
        return __('enums.currency.'.$this->value);
    }

    /**
     * All currencies selectable for a tenant, value => label.
     */
    public static function options(): array
    {
        return [
            self::USD->value => self::USD->getLabel(),
            self::EUR->value => self::EUR->getLabel(),
            self::GBP->value => self::GBP->getLabel(),
            self::AED->value => self::AED->getLabel(),
            self::SAR->value => self::SAR->getLabel(),
            self::EGP->value => self::EGP->getLabel(),
            self::JOD->value => self::JOD->getLabel(),
            self::LBP->value => self::LBP->getLabel(),
            self::CAD->value => self::CAD->getLabel(),
            self::AUD->value => self::AUD->getLabel(),
        ];
    }
}
