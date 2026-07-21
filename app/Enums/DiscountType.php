<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountType: string implements HasLabel
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage',
            self::Fixed => 'Fixed amount',
        };
    }

    public static function options(): array
    {
        return [
            self::Percentage->value => self::Percentage->getLabel(),
            self::Fixed->value => self::Fixed->getLabel(),
        ];
    }

    /**
     * The discount amount a type+value produces against a given base, clamped
     * so it can never exceed the base (no negative totals).
     */
    public static function amount(?self $type, ?float $value, float $base): float
    {
        if ($type === null || blank($value) || $value <= 0) {
            return 0;
        }

        $amount = $type === self::Percentage
            ? $base * ($value / 100)
            : $value;

        return min($amount, $base);
    }
}
