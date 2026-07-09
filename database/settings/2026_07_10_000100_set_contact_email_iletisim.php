<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // İletişim e-postasını iletisim@organikexpress.com yap — sipariş ve iletişim
        // maillerinin geldiği adresle aynı olsun. Kullanıcı özel bir adres girmişse
        // (info@/organik.test dışında) DOKUNMA; sadece eski varsayılanı değiştir.
        $this->migrator->update('contact.email', function ($v) {
            $v = (string) $v;

            return ($v === '' || str_contains($v, 'organik.test') || $v === 'info@organikexpress.com')
                ? 'iletisim@organikexpress.com'
                : $v;
        });
    }

    public function down(): void
    {
        // Geri alma gerekmez (iletişim bilgisi).
    }
};
