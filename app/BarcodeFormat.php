<?php

namespace App;

enum BarcodeFormat
{
    const EAN13 = 'ean13';
    const EAN8 = 'ean8';
    const UPCA = 'upca';
    const CODE128 = 'code128';
    const CODE39 = 'code39';

    /**
     * All barcode symbologies selectable for a tenant, value => label.
     */
    public static function options(): array
    {
        return [
            self::EAN13 => 'EAN-13',
            self::EAN8 => 'EAN-8',
            self::UPCA => 'UPC-A',
            self::CODE128 => 'Code 128',
            self::CODE39 => 'Code 39',
        ];
    }
}
