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
        return __('enums.stock_adjustment_reason.'.$this->value);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
