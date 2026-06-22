<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ThemeSettings extends Settings
{
    public string $primary_color;      // marka (yeşil) — hex
    public string $accent_color;       // aksan (kil/toprak) — hex
    public string $heading_font;       // başlık fontu (Google Font adı)
    public string $body_font;          // gövde fontu
    public bool $announcement_enabled;
    public ?string $announcement_text;

    public static function group(): string
    {
        return 'theme';
    }
}
