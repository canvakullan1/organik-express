<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Kargo politikası güncellemesi: 3.750 TL altındaki siparişlerde 500 TL kargo,
 * 3.750 TL ve üzeri ücretsiz kargo.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->update('general.free_shipping_threshold', fn () => 3750);
        $this->migrator->update('checkout.shipping_cost', fn () => 500);
    }

    public function down(): void
    {
        $this->migrator->update('general.free_shipping_threshold', fn () => 750);
        $this->migrator->update('checkout.shipping_cost', fn () => 49.90);
    }
};
