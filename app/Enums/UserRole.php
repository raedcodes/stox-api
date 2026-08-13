<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case SuperAdmin = 'super-admin';
    case Owner = 'owner';
    case Manager = 'manager';
    case Cashier = 'cashier';
    case StoreKeeper = 'storekeeper';

    public function getLabel(): string
    {
        return __('enums.user_role.'.$this->value);
    }

    /**
     * All roles a super-admin may assign to another user, value => label.
     */
    public static function options(): array
    {
        return [
            self::Owner->value => self::Owner->getLabel(),
            self::Manager->value => self::Manager->getLabel(),
            self::Cashier->value => self::Cashier->getLabel(),
            self::StoreKeeper->value => self::StoreKeeper->getLabel(),
        ];
    }
}
