<?php

namespace App;

enum Currency
{
    const USD = 'USD';
    const EUR = 'EUR';
    const GBP = 'GBP';
    const AED = 'AED';
    const SAR = 'SAR';
    const EGP = 'EGP';
    const JOD = 'JOD';
    const LBP = 'LBP';
    const CAD = 'CAD';
    const AUD = 'AUD';

    /**
     * All currencies selectable for a tenant, value => label.
     */
    public static function options(): array
    {
        return [
            self::USD => 'USD - US Dollar',
            self::EUR => 'EUR - Euro',
            self::GBP => 'GBP - British Pound',
            self::AED => 'AED - UAE Dirham',
            self::SAR => 'SAR - Saudi Riyal',
            self::EGP => 'EGP - Egyptian Pound',
            self::JOD => 'JOD - Jordanian Dinar',
            self::LBP => 'LBP - Lebanese Pound',
            self::CAD => 'CAD - Canadian Dollar',
            self::AUD => 'AUD - Australian Dollar',
        ];
    }
}
