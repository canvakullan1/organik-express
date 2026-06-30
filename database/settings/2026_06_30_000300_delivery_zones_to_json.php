<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // delivery_zones'u dizi -> JSON metni'ne çevir (spatie cast hatasını önler).
        $this->migrator->update('checkout.delivery_zones', function ($value) {
            if (is_string($value)) {
                return $value; // zaten JSON metni
            }

            return json_encode($value ?: [
                ['name' => 'İstanbul (Avrupa Yakası)', 'days' => [6]],
                ['name' => 'İstanbul (Anadolu Yakası)', 'days' => [3, 0]],
            ], JSON_UNESCAPED_UNICODE);
        });
    }

    public function down(): void
    {
        $this->migrator->update('checkout.delivery_zones', function ($value) {
            return is_string($value) ? (json_decode($value, true) ?: []) : $value;
        });
    }
};
