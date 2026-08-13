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
        return __('enums.stock_movement_type.'.$this->value);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
