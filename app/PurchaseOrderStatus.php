<?php

namespace App;

enum PurchaseOrderStatus
{
    const Draft = 'draft';
    const Ordered = 'ordered';
    const PartiallyReceived = 'partially_received';
    const Received = 'received';
    const Cancelled = 'cancelled';

    /**
     * All purchase order statuses, value => label.
     */
    public static function options(): array
    {
        return [
            self::Draft => 'Draft',
            self::Ordered => 'Ordered',
            self::PartiallyReceived => 'Partially received',
            self::Received => 'Received',
            self::Cancelled => 'Cancelled',
        ];
    }

    public static function colors(): array
    {
        return [
            self::Draft => 'gray',
            self::Ordered => 'info',
            self::PartiallyReceived => 'warning',
            self::Received => 'success',
            self::Cancelled => 'danger',
        ];
    }
}
