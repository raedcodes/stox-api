<?php

namespace App;

enum PaymentMethod
{
    const Cash = 'cash';
    const Card = 'card';
    const Credit = 'credit';

    public static function options(): array
    {
        return [
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::Credit => 'Credit (pay later)',
        ];
    }
}
