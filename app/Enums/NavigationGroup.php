<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup implements HasLabel
{
    case Sales;
    case Purchasing;
    case Catalog;
    case Inventory;
    case Reports;
    case Settings;
    case System;

    public function getLabel(): string
    {
        return match ($this) {
            self::Sales => 'Sales',
            self::Purchasing => 'Purchasing',
            self::Catalog => 'Catalog',
            self::Inventory => 'Inventory',
            self::Reports => 'Reports',
            self::Settings => 'Settings',
            self::System => 'System',
        };
    }
}
