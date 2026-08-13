<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PurchaseOrderStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Ordered = 'ordered';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __('enums.purchase_order_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingApproval => 'warning',
            self::Ordered => 'info',
            self::PartiallyReceived => 'warning',
            self::Received => 'success',
            self::Rejected => 'danger',
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
            self::PendingApproval->value => self::PendingApproval->getLabel(),
            self::Ordered->value => self::Ordered->getLabel(),
            self::PartiallyReceived->value => self::PartiallyReceived->getLabel(),
            self::Received->value => self::Received->getLabel(),
            self::Rejected->value => self::Rejected->getLabel(),
            self::Cancelled->value => self::Cancelled->getLabel(),
        ];
    }

    public static function colors(): array
    {
        return [
            self::Draft->value => self::Draft->getColor(),
            self::PendingApproval->value => self::PendingApproval->getColor(),
            self::Ordered->value => self::Ordered->getColor(),
            self::PartiallyReceived->value => self::PartiallyReceived->getColor(),
            self::Received->value => self::Received->getColor(),
            self::Rejected->value => self::Rejected->getColor(),
            self::Cancelled->value => self::Cancelled->getColor(),
        ];
    }
}
