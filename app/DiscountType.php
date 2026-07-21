<?php

namespace App;

enum DiscountType
{
    const Percentage = 'percentage';
    const Fixed = 'fixed';

    public static function options(): array
    {
        return [
            self::Percentage => 'Percentage',
            self::Fixed => 'Fixed amount',
        ];
    }

    /**
     * The discount amount a type+value produces against a given base, clamped
     * so it can never exceed the base (no negative totals).
     */
    public static function amount(?string $type, ?float $value, float $base): float
    {
        if (blank($type) || blank($value) || $value <= 0) {
            return 0;
        }

        $amount = $type === self::Percentage
            ? $base * ($value / 100)
            : $value;

        return min($amount, $base);
    }
}
