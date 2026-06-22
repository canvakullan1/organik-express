<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CheckoutSettings extends Settings
{
    public float $shipping_cost;            // eşik altı kargo ücreti
    public bool $cash_on_delivery_enabled;
    public float $cash_on_delivery_fee;

    // Havale / EFT bilgileri
    public ?string $bank_name;
    public ?string $bank_account_holder;
    public ?string $bank_iban;

    // Teslimat
    public int $delivery_lead_days;         // en erken teslim = bugün + N gün
    /** @var array<int, string> */
    public array $delivery_slots;           // zaman aralıkları

    public static function group(): string
    {
        return 'checkout';
    }
}
