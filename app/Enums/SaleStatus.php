<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SaleStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __('enums.sale_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public static function options(): array
    {
        return [
            self::Draft->value => self::Draft->getLabel(),
            self::Completed->value => self::Completed->getLabel(),
            self::Cancelled->value => self::Cancelled->getLabel(),
        ];
    }

    public static function colors(): array
    {
        return [
            self::Draft->value => self::Draft->getColor(),
            self::Completed->value => self::Completed->getColor(),
            self::Cancelled->value => self::Cancelled->getColor(),
        ];
    }
}
