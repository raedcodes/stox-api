<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExpenseCategory: string implements HasLabel
{
    case Rent = 'rent';
    case Utilities = 'utilities';
    case Salaries = 'salaries';
    case Marketing = 'marketing';
    case Supplies = 'supplies';
    case Insurance = 'insurance';
    case Maintenance = 'maintenance';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('enums.expense_category.'.$this->value);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
