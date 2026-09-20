<?php

namespace App\Console\Commands;

use App\Settings\CheckoutSettings;
use Illuminate\Console\Command;

/**
 * Erken sipariş %indirimini kapatır (mazot/nakliye maliyeti arttığı için
 * karşılanamıyor). Ayarı 0 yapmak yeterli: hem gerçek fiyat hesaplamasını
 * (OrderService::earlyDiscountPercent) hem checkout sayfasındaki ipucu
 * kutularını (@if($earlyPct > 0)) otomatik devre dışı bırakır.
 *
 *   php artisan checkout:disable-early-discount
 */
class DisableEarlyOrderDiscount extends Command
{
    protected $signature = 'checkout:disable-early-discount';

    protected $description = 'Erken sipariş indirimi yüzdesini 0 yapar';

    public function handle(): int
    {
        $s = app(CheckoutSettings::class);
        $before = $s->early_order_discount_percent;
        $s->early_order_discount_percent = 0;
        $s->save();

        $this->info("early_order_discount_percent: {$before} -> 0");

        return self::SUCCESS;
    }
}
