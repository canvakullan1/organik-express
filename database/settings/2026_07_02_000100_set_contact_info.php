<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $phone = '0532 281 96 67';
        $address = 'Adnan Kahveci Mah. Osmanlı Cad. Mutlu Home Sitesi, 4. Blok C Giriş, No: 28A İç Kapı No: 12, Beylikdüzü / İstanbul';

        $this->migrator->update('contact.phone', fn () => $phone);
        $this->migrator->update('contact.whatsapp', fn () => $phone);
        $this->migrator->update('contact.address', fn () => $address);

        // Placeholder e-posta (info@organik.test) gerçek adresle değiştir
        $this->migrator->update('contact.email', function ($v) {
            return (! $v || str_contains((string) $v, 'organik.test')) ? 'info@organikexpress.com' : $v;
        });
    }

    public function down(): void
    {
        // Geri alma gerekmez (iletişim bilgileri).
    }
};
