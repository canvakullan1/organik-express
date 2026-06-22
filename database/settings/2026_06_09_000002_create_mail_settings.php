<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('mail.mailer', 'log');       // SMTP girilene kadar log
        $this->migrator->add('mail.host', '');
        $this->migrator->add('mail.port', 587);
        $this->migrator->add('mail.username', '');
        $this->migrator->addEncrypted('mail.password', '');
        $this->migrator->add('mail.encryption', 'tls');
        $this->migrator->add('mail.from_address', 'siparis@organik.test');
        $this->migrator->add('mail.from_name', 'Organik Ürün');
    }
};
