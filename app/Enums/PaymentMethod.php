<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case Cash = 'cash';
    case Card = 'card';
    case Credit = 'credit';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::Credit => 'Credit (pay later)',
        };
    }

    public static function options(): array
    {
        return [
            self::Cash->value => self::Cash->getLabel(),
            self::Card->value => self::Card->getLabel(),
            self::Credit->value => self::Credit->getLabel(),
        ];
    }
}
