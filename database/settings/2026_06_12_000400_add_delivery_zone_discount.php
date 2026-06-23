<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Elden teslimat yapılan şehirler (bu şehirlerde teslim tarihi seçilir + erken sipariş indirimi geçerli)
        $this->migrator->add('checkout.delivery_zone_cities', ['İstanbul']);
        // Teslim gününden 1 gün önce verilen siparişlere uygulanan indirim yüzdesi
        $this->migrator->add('checkout.early_order_discount_percent', 10);
    }

    public function down(): void
    {
        $this->migrator->delete('checkout.delivery_zone_cities');
        $this->migrator->delete('checkout.early_order_discount_percent');
    }
};
