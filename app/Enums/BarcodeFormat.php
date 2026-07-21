<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BarcodeFormat: string implements HasLabel
{
    case EAN13 = 'ean13';
    case EAN8 = 'ean8';
    case UPCA = 'upca';
    case CODE128 = 'code128';
    case CODE39 = 'code39';

    public function getLabel(): string
    {
        return match ($this) {
            self::EAN13 => 'EAN-13',
            self::EAN8 => 'EAN-8',
            self::UPCA => 'UPC-A',
            self::CODE128 => 'Code 128',
            self::CODE39 => 'Code 39',
        };
    }

    /**
     * All barcode symbologies selectable for a tenant, value => label.
     */
    public static function options(): array
    {
        return [
            self::EAN13->value => self::EAN13->getLabel(),
            self::EAN8->value => self::EAN8->getLabel(),
            self::UPCA->value => self::UPCA->getLabel(),
            self::CODE128->value => self::CODE128->getLabel(),
            self::CODE39->value => self::CODE39->getLabel(),
        ];
    }
}
