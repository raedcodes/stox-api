<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StockMovementType: string implements HasLabel
{
    case PurchaseReceipt = 'purchase_receipt';
    case Sale = 'sale';
    case SaleReturn = 'sale_return';
    case SaleCancelled = 'sale_cancelled';
    case PurchaseReturn = 'purchase_return';
    case Adjustment = 'adjustment';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case OpeningStock = 'opening_stock';

    public function getLabel(): string
    {
        return match ($this) {
            self::PurchaseReceipt => 'Purchase receipt',
            self::Sale => 'Sale',
            self::SaleReturn => 'Sale return',
            self::SaleCancelled => 'Sale cancelled',
            self::PurchaseReturn => 'Purchase return',
            self::Adjustment => 'Adjustment',
            self::TransferOut => 'Transfer out',
            self::TransferIn => 'Transfer in',
            self::OpeningStock => 'Opening stock',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
