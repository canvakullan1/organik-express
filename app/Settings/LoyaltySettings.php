<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Para puan kuralları. 1 puan = 1 ₺ değerindedir.
 */
class LoyaltySettings extends Settings
{
    public bool $enabled;
    public float $earn_rate;            // sipariş tutarının % kadarı puan olarak kazanılır
    public float $max_redeem_percent;   // sepetin en fazla %'si puanla ödenebilir
    public float $min_balance_to_redeem; // kullanım için min bakiye
    public float $min_order_to_earn;     // kazanım için min sipariş tutarı

    public static function group(): string
    {
        return 'loyalty';
    }
}
