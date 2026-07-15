<?php

namespace App;

enum BusinessType
{
    const Retail = 'retail';
    const Restaurant = 'restaurant';
    const Pharmacy = 'pharmacy';
    const Wholesale = 'wholesale';
    const Other = 'other';

    /**
     * All business types selectable during tenant onboarding, value => label.
     */
    public static function options(): array
    {
        return [
            self::Retail => 'Retail',
            self::Restaurant => 'Restaurant',
            self::Pharmacy => 'Pharmacy',
            self::Wholesale => 'Wholesale',
            self::Other => 'Other',
        ];
    }
}
