<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockTransferStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case InTransit = 'in_transit';
    case PartiallyReceived = 'partially_received';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingApproval => 'Pending approval',
            self::Approved => 'Approved',
            self::InTransit => 'In transit',
            self::PartiallyReceived => 'Partially received',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Rejected => 'Rejected',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingApproval => 'warning',
            self::Approved => 'primary',
            self::InTransit => 'info',
            self::PartiallyReceived => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
            self::Rejected => 'danger',
        };
    }

    /**
     * Statuses a transfer can still be edited, deleted, or completed from —
     * anything that hasn't reached a final outcome yet.
     */
    public static function openStatuses(): array
    {
        return [
            self::Draft,
            self::PendingApproval,
            self::Approved,
            self::InTransit,
            self::PartiallyReceived,
        ];
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
