<?php

namespace App;

enum SaleStatus
{
    const Draft = 'draft';
    const Completed = 'completed';
    const Cancelled = 'cancelled';

    public static function options(): array
    {
        return [
            self::Draft => 'Draft',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        ];
    }

    public static function colors(): array
    {
        return [
            self::Draft => 'gray',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        ];
    }
}
