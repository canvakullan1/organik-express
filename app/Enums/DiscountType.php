<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountType: string implements HasLabel
{
    case Percent = 'percent';
    case Amount = 'amount';

    public function getLabel(): string
    {
        return match ($this) {
            self::Percent => 'Yüzde (%)',
            self::Amount => 'Tutar (₺)',
        };
    }
}
