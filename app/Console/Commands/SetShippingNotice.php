<?php

namespace App\Console\Commands;

use App\Settings\ThemeSettings;
use Illuminate\Console\Command;

/**
 * Site geneli duyuru bandına ("·" ile ayrılan çoklu mesaj rotasyonu, bkz.
 * layouts/storefront.blade.php) teslimat kapsamı notunu ekler. Var olan
 * mesajları SİLMEZ — zaten ekliyse tekrar eklemez (idempotent).
 *
 *   php artisan site:set-shipping-notice
 *   php artisan site:set-shipping-notice --dry-run
 */
class SetShippingNotice extends Command
{
    protected $signature = 'site:set-shipping-notice {--dry-run}';

    protected $description = 'Duyuru bandına süt/et/taze meyve teslimat notunu ekler';

    private string $message = "Süt, et ve narin meyveler dışındaki siparişleri Türkiye'nin her yerine gönderiyoruz";

    public function handle(): int
    {
        $settings = app(ThemeSettings::class);

        $current = trim((string) ($settings->announcement_text ?? ''));
        $parts = $current === '' ? [] : array_map('trim', explode('·', $current));

        if (in_array($this->message, $parts, true)) {
            $this->info('Mesaj zaten duyuru bandında, değişiklik yapılmadı.');
            $this->line('Mevcut metin: ' . $current);
            $this->line('announcement_enabled: ' . var_export($settings->announcement_enabled, true));

            return self::SUCCESS;
        }

        $parts[] = $this->message;
        $newText = implode(' · ', array_filter($parts, fn ($p) => $p !== ''));

        $this->line('Önceki metin : ' . ($current ?: '(boş)'));
        $this->line('Yeni metin   : ' . $newText);
        $this->line('enabled      : true' . ($settings->announcement_enabled ? ' (zaten açıktı)' : ' (açılıyor)'));

        if ($this->option('dry-run')) {
            $this->info('[DRY-RUN] Kaydedilmedi.');

            return self::SUCCESS;
        }

        $settings->announcement_text = $newText;
        $settings->announcement_enabled = true;
        $settings->save();

        $this->info('Duyuru bandı güncellendi.');

        return self::SUCCESS;
    }
}
