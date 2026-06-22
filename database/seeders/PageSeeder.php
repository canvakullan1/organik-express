<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        // [başlık, footer_group, içerik-taslağı]
        $pages = [
            ['Hakkımızda', 'kurumsal', '<p>Çiftlikten sofraya, sertifikalı ve analizli organik gıdayı buluşturuyoruz. Üreticisi belli, izlenebilir ürünler sunmak için çalışıyoruz.</p>'],
            ['Üreticilerimiz', 'kurumsal', '<p>Birlikte çalıştığımız üreticiler ve çiftliklerin hikâyelerini burada paylaşıyoruz.</p>'],
            ['Sertifikalar', 'kurumsal', '<p>Organik sertifikalarımız ve pestisit analiz raporlarımız.</p>'],
            ['Sıkça Sorulan Sorular', 'yardim', '<p>Aklınıza takılan soruların yanıtlarını burada bulabilirsiniz.</p>'],
            ['Teslimat & Dağıtım', 'yardim', '<p>Teslimat günleri, bölgeler ve soğuk zincir hakkında bilgiler.</p>'],
            ['İptal & İade Koşulları', 'yardim', '<p>Çabuk bozulan/taze gıda ürünlerinde cayma hakkının niteliği ve iade koşulları.</p>'],
            ['İletişim', 'yardim', '<p>Bize ulaşın.</p>'],
            ['Mesafeli Satış Sözleşmesi', 'yasal', '<p><em>Bu metin yer tutucudur. Lütfen hukuk danışmanınızın onayladığı metni admin panelinden girin.</em></p>'],
            ['Ön Bilgilendirme Formu', 'yasal', '<p><em>Yer tutucu — hukuk danışmanı onaylı metin admin panelinden girilmelidir.</em></p>'],
            ['KVKK Aydınlatma Metni', 'yasal', '<p><em>Yer tutucu — KVKK aydınlatma metni admin panelinden girilmelidir.</em></p>'],
            ['Gizlilik & Güvenlik Politikası', 'yasal', '<p><em>Yer tutucu metin.</em></p>'],
            ['Kullanım Koşulları', 'yasal', '<p><em>Yer tutucu metin.</em></p>'],
        ];

        $sort = 0;
        foreach ($pages as [$title, $group, $content]) {
            Page::firstOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'content' => $content,
                    'excerpt' => null,
                    'is_published' => true,
                    'show_in_footer' => true,
                    'footer_group' => $group,
                    'sort_order' => $sort++,
                ]
            );
        }
    }
}
