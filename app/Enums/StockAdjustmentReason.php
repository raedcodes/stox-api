<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StockAdjustmentReason: string implements HasLabel
{
    case Damage = 'damage';
    case Loss = 'loss';
    case Correction = 'correction';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Damage => 'Damage',
            self::Loss => 'Loss',
            self::Correction => 'Correction',
            self::Other => 'Other',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
