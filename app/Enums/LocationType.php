<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LocationType: string implements HasLabel
{
    case Warehouse = 'warehouse';
    case Store = 'store';

    public function getLabel(): string
    {
        return __('enums.location_type.'.$this->value);
    }

    /**
     * All location types selectable for a tenant, value => label.
     */
    public static function options(): array
    {
        return [
            self::Warehouse->value => self::Warehouse->getLabel(),
            self::Store->value => self::Store->getLabel(),
        ];
    }
}
