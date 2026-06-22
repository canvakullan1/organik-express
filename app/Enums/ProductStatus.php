<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';
    case Active = 'active';
    case Passive = 'passive';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Taslak',
            self::Active => 'Yayında',
            self::Passive => 'Pasif',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Active => 'success',
            self::Passive => 'danger',
        };
    }
}
