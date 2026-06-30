<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $default = "Teslimat günleri bölgeye göre değişir:\n"
            . "• İstanbul (Avrupa Yakası): Cumartesi\n"
            . "• İstanbul (Anadolu Yakası): Çarşamba ve Pazar\n"
            . "Diğer iller, anlaşmalı kargo ile 1-3 iş günü içinde gönderilir.";

        $this->migrator->add('checkout.delivery_info_note', $default);
    }

    public function down(): void
    {
        $this->migrator->delete('checkout.delivery_info_note');
    }
};
