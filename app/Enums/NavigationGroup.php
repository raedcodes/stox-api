<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup implements HasLabel
{
    case Sales;
    case Purchasing;
    case Catalog;
    case Inventory;
    case Finance;
    case Reports;
    case Settings;
    case System;

    public function getLabel(): string
    {
        return match ($this) {
            self::Sales => __('enums.navigation_group.sales'),
            self::Purchasing => __('enums.navigation_group.purchasing'),
            self::Catalog => __('enums.navigation_group.catalog'),
            self::Inventory => __('enums.navigation_group.inventory'),
            self::Finance => __('enums.navigation_group.finance'),
            self::Reports => __('enums.navigation_group.reports'),
            self::Settings => __('enums.navigation_group.settings'),
            self::System => __('enums.navigation_group.system'),
        };
    }
}
