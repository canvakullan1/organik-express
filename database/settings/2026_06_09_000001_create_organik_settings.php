<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Genel
        $this->migrator->add('general.site_name', 'Organik Ürün');
        $this->migrator->add('general.tagline', 'Çiftlikten sofraya, sertifikalı organik gıda');
        $this->migrator->add('general.logo', null);
        $this->migrator->add('general.favicon', null);
        $this->migrator->add('general.footer_about', 'Çiftlikten sofraya, sertifikalı ve analizli organik gıda. Üreticisi belli, izlenebilir.');
        $this->migrator->add('general.free_shipping_threshold', 750);
        $this->migrator->add('general.maintenance_mode', false);

        // Görünüm / Tipografi
        $this->migrator->add('theme.primary_color', '#3f8f2e');
        $this->migrator->add('theme.accent_color', '#e0552b');
        $this->migrator->add('theme.heading_font', 'Manrope');
        $this->migrator->add('theme.body_font', 'Plus Jakarta Sans');
        $this->migrator->add('theme.announcement_enabled', true);
        $this->migrator->add('theme.announcement_text', '750 TL üzeri siparişlerde kargo bedava · Sertifikalı & pestisit analizli ürünler');

        // İletişim
        $this->migrator->add('contact.phone', '');
        $this->migrator->add('contact.email', 'info@organik.test');
        $this->migrator->add('contact.whatsapp', '');
        $this->migrator->add('contact.address', '');
        $this->migrator->add('contact.working_hours', 'Hafta içi 09:00 - 18:00');
        $this->migrator->add('contact.map_embed', '');

        // Sosyal medya
        $this->migrator->add('social.instagram', '');
        $this->migrator->add('social.facebook', '');
        $this->migrator->add('social.x', '');
        $this->migrator->add('social.youtube', '');
        $this->migrator->add('social.linkedin', '');
        $this->migrator->add('social.tiktok', '');

        // SEO / Analitik
        $this->migrator->add('seo.meta_title', 'Organik Ürün — Çiftlikten Sofraya Sertifikalı Organik');
        $this->migrator->add('seo.meta_description', 'Sertifikalı, analizli, üreticisi belli organik gıda ve doğal ürünler.');
        $this->migrator->add('seo.og_image', null);
        $this->migrator->add('seo.google_analytics_id', '');
        $this->migrator->add('seo.gtm_id', '');
    }
};
