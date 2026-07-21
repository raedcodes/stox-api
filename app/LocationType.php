<?php

namespace App;

enum LocationType
{
    const Warehouse = 'warehouse';
    const Store = 'store';

    /**
     * All location types selectable for a tenant, value => label.
     */
    public static function options(): array
    {
        return [
            self::Warehouse => 'Warehouse',
            self::Store => 'Store',
        ];
    }
}
