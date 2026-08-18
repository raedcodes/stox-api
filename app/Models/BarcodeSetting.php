<?php

namespace App\Models;

use App\Enums\BarcodeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BarcodeSetting extends Model
{
    protected $fillable = [
        'format',
        'prefix',
        'next_number',
    ];

    protected function casts(): array
    {
        return [
            'format' => BarcodeFormat::class,
            'next_number' => 'integer',
        ];
    }

    /**
     * The current tenant's barcode settings. Created on demand in case a
     * tenant was provisioned before this table existed and hasn't been
     * re-migrated.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'format' => BarcodeFormat::CODE128,
            'next_number' => 1,
        ]);
    }

    /**
     * Number of data digits (excluding the check digit) each numeric,
     * fixed-length symbology encodes. CODE128/CODE39 have no fixed length
     * and no checksum, so they're absent here.
     */
    protected const DATA_DIGITS = [
        'ean13' => 12,
        'ean8' => 7,
        'upca' => 11,
    ];

    /**
     * Generates the next barcode in this tenant's configured sequence and
     * atomically advances next_number, so two concurrent product/variant
     * creates can never be handed the same value — locks the row for the
     * duration of the caller's transaction, the same protection
     * ProductBatch::deductFefo() uses for concurrent stock deductions.
     */
    public function next(): string
    {
        return DB::transaction(function () {
            $setting = static::query()->lockForUpdate()->findOrFail($this->id);
            $format = $setting->format;
            $sequence = $setting->prefix.$setting->next_number;

            $setting->increment('next_number');

            $dataDigits = self::DATA_DIGITS[$format->value] ?? null;

            if ($dataDigits === null) {
                return $sequence;
            }

            if (strlen($sequence) > $dataDigits) {
                throw new RuntimeException(
                    "Barcode sequence exhausted for {$format->getLabel()} with prefix \"{$setting->prefix}\" — ".
                    "it needs {$dataDigits} digits but \"{$sequence}\" has ".strlen($sequence).'. '.
                    'Shorten the prefix, reset the next number, or switch to a format without a fixed length.'
                );
            }

            $digits = str_pad($sequence, $dataDigits, '0', STR_PAD_LEFT);

            return $digits.self::checkDigit($digits);
        });
    }

    /**
     * The standard GS1 check-digit algorithm shared by EAN-13, EAN-8, and
     * UPC-A: reading the data digits from the rightmost one, alternate
     * weights 3, 1, 3, 1…
     */
    protected static function checkDigit(string $digits): int
    {
        $sum = 0;
        $weight = 3;

        foreach (str_split(strrev($digits)) as $digit) {
            $sum += ((int) $digit) * $weight;
            $weight = $weight === 3 ? 1 : 3;
        }

        return (10 - ($sum % 10)) % 10;
    }
}
