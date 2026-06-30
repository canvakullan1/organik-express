<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CheckoutSettings extends Settings
{
    public float $shipping_cost;            // eşik altı kargo ücreti
    public bool $cash_on_delivery_enabled;
    public float $cash_on_delivery_fee;

    // Teslimat
    public int $delivery_lead_days;         // en erken teslim = bugün + N gün
    /** @var array<int, string> */
    public array $delivery_slots;           // zaman aralıkları
    public ?string $delivery_info_note;     // bölgeye göre teslim günleri bilgilendirme notu
    /** @var array Elden teslim bölgeleri: [['name'=>..., 'days'=>[0..6]], ...] (0=Pazar..6=Cumartesi) */
    public array $delivery_zones;

    // Teslimat bölgeleri (elden teslim yapılan + erken sipariş indirimi geçerli şehirler)
    /** @var array<int, string> */
    public array $delivery_zone_cities;
    public int $early_order_discount_percent;   // teslim gününden 1 gün önce → bu % indirim

    public static function group(): string
    {
        return 'checkout';
    }
}
