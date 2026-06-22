<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Settings\GeneralSettings;
use App\Settings\ThemeSettings;
use Illuminate\Database\Seeder;

/**
 * Müşteriden gelen gerçek marka/içerik bilgileri (Organik Express).
 * Tümü admin panelinden de düzenlenebilir.
 */
class BrandInfoSeeder extends Seeder
{
    public function run(): void
    {
        $g = app(GeneralSettings::class);
        $g->site_name = 'Organik Express';
        $g->tagline = 'Her hafta kapınıza taze organik meyve ve sebze';
        $g->footer_about = 'Her hafta kapınıza taze organik meyve ve sebze teslim ediyoruz. Ürünlerimiz kendi tarlalarımızdan ve yetiştirici dostlarımızdan; tamamı yerel ve uluslararası sertifikalı.';
        $g->save();

        $t = app(ThemeSettings::class);
        $t->announcement_text = 'Teslimat gününden 1 gün önce sipariş ver, %10 indirim kazan · Kapınıza ücretsiz haftalık teslimat · Yerel & uluslararası sertifikalı';
        $t->save();

        // Teslimat & Dağıtım sayfası içeriği (bölgeler + günler)
        $deliveryHtml = <<<'HTML'
<h2>Teslimat Yaptığımız Bölgeler</h2>
<ul>
    <li>İstanbul Avrupa Yakası</li>
    <li>İstanbul Anadolu Yakası</li>
    <li>Diğer Şehirler</li>
</ul>
<h2>Teslimat Günleri</h2>
<ul>
    <li><strong>İstanbul Avrupa Yakası:</strong> Cumartesi</li>
    <li><strong>İstanbul Anadolu Yakası:</strong> Çarşamba - Pazar</li>
</ul>
<p>Bölgeniz için haftalık teslimatları belirli bir günde alırsınız. Teslimat gününden <strong>1 gün önce</strong> vereceğiniz siparişlerde <strong>%10 indirim</strong> kazanırsınız.</p>
<p>Taze ürünlerde soğuk zincir korunur; siparişiniz özenle paketlenip kapınıza ücretsiz teslim edilir.</p>
HTML;

        $page = Page::where('slug', 'teslimat-dagitim')->first();
        if ($page) {
            $page->update(['content' => $deliveryHtml, 'excerpt' => 'Teslimat bölgelerimiz, günlerimiz ve avantajlı sipariş koşulları.']);
        }
    }
}
