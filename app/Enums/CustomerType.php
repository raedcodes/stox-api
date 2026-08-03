<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CustomerType: string implements HasLabel
{
    case B2B = 'b2b';
    case B2C = 'b2c';

    public function getLabel(): string
    {
        return match ($this) {
            self::B2B => 'Business (B2B)',
            self::B2C => 'Individual (B2C)',
        };
    }

    /**
     * All customer types selectable on a customer record, value => label.
     */
    public static function options(): array
    {
        return [
            self::B2B->value => self::B2B->getLabel(),
            self::B2C->value => self::B2C->getLabel(),
        ];
    }
}
