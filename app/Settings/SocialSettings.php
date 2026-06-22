<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SocialSettings extends Settings
{
    public ?string $instagram;
    public ?string $facebook;
    public ?string $x;
    public ?string $youtube;
    public ?string $linkedin;
    public ?string $tiktok;

    public static function group(): string
    {
        return 'social';
    }
}
