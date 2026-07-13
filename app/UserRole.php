<?php

namespace App;

enum UserRole
{
    const SuperAdmin = 'super-admin';
    const Owner = 'owner';
    const Manager = 'manager';
    const Cashier = 'cashier';
    const StoreKeeper = 'storekeeper';

    /**
     * All roles a super-admin may assign to another user, value => label.
     */
    public static function options(): array
    {
        return [
            self::Owner => 'Owner',
            self::Manager => 'Manager',
            self::Cashier => 'Cashier',
            self::StoreKeeper => 'Storekeeper',
        ];
    }
}
