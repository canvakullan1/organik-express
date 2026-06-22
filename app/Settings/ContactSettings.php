<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ContactSettings extends Settings
{
    public ?string $phone;
    public ?string $email;
    public ?string $whatsapp;
    public ?string $address;
    public ?string $working_hours;
    public ?string $map_embed;

    public static function group(): string
    {
        return 'contact';
    }
}
