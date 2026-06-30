<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Gün numaraları JS getDay()/Carbon dayOfWeek ile aynı: 0=Pazar, 3=Çarşamba, 6=Cumartesi
        $this->migrator->add('checkout.delivery_zones', [
            ['name' => 'İstanbul (Avrupa Yakası)', 'days' => [6]],
            ['name' => 'İstanbul (Anadolu Yakası)', 'days' => [3, 0]],
        ]);
    }

    public function down(): void
    {
        $this->migrator->delete('checkout.delivery_zones');
    }
};
