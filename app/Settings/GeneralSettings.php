<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;
    public ?string $tagline;
    public ?string $logo;
    public ?string $favicon;
    public ?string $footer_about;
    public int $free_shipping_threshold;
    public bool $maintenance_mode;

    public static function group(): string
    {
        return 'general';
    }

    /** Dosya yolu alanları (Filament FileUpload ile yönetilir). */
    public static function encrypted(): array
    {
        return [];
    }
}
