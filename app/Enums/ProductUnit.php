<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Satış birimi. Ağırlık bazlı (kg/gr) ürünlerde tartım sonrası
 * fiyat farkı oluşabileceği vitrinde belirtilir.
 */
enum ProductUnit: string implements HasLabel
{
    case Kilogram = 'kg';
    case Gram = 'gr';
    case Adet = 'adet';
    case Demet = 'demet';
    case Paket = 'paket';

    public function getLabel(): string
    {
        return match ($this) {
            self::Kilogram => 'Kilogram (kg)',
            self::Gram => 'Gram (gr)',
            self::Adet => 'Adet',
            self::Demet => 'Demet',
            self::Paket => 'Paket',
        };
    }

    /** Ağırlık bazlı (tartımla fiyatı değişebilen) birim mi? */
    public function isWeightBased(): bool
    {
        return in_array($this, [self::Kilogram, self::Gram], true);
    }
}
