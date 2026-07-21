<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PurchaseOrderStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Ordered = 'ordered';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Ordered => 'Ordered',
            self::PartiallyReceived => 'Partially received',
            self::Received => 'Received',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Ordered => 'info',
            self::PartiallyReceived => 'warning',
            self::Received => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * All purchase order statuses, value => label.
     */
    public static function options(): array
    {
        return [
            self::Draft->value => self::Draft->getLabel(),
            self::Ordered->value => self::Ordered->getLabel(),
            self::PartiallyReceived->value => self::PartiallyReceived->getLabel(),
            self::Received->value => self::Received->getLabel(),
            self::Cancelled->value => self::Cancelled->getLabel(),
        ];
    }

    public static function colors(): array
    {
        return [
            self::Draft->value => self::Draft->getColor(),
            self::Ordered->value => self::Ordered->getColor(),
            self::PartiallyReceived->value => self::PartiallyReceived->getColor(),
            self::Received->value => self::Received->getColor(),
            self::Cancelled->value => self::Cancelled->getColor(),
        ];
    }
}
