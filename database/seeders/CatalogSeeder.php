<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Şartname Bölüm 1'deki ana kategoriler ve örnek alt kategoriler.
        $tree = [
            'Taze Meyve & Sebze' => ['Meyveler', 'Sebzeler', 'Yeşillikler', 'Mevsim Ürünleri'],
            'Kahvaltılık' => ['Peynirler', 'Bal & Reçel', 'Tereyağı', 'Yumurta'],
            'Zeytin & Zeytinyağı' => ['Sızma Zeytinyağı', 'Yeşil Zeytin', 'Siyah Zeytin'],
            'Kuru Gıda' => ['Baklagiller', 'Bakliyat & Tahıl', 'Kuruyemiş', 'Un & Makarna'],
            'Et, Tavuk & Balık' => ['Kırmızı Et', 'Tavuk', 'Balık & Deniz Ürünleri'],
            'Temizlik' => ['Çamaşır', 'Bulaşık', 'Yüzey Temizliği'],
            'Kişisel Bakım' => ['Saç Bakımı', 'Cilt Bakımı', 'Ağız Bakımı'],
        ];

        $sort = 0;
        foreach ($tree as $parentName => $children) {
            $parent = Category::firstOrCreate(
                ['slug' => str()->slug($parentName)],
                ['name' => $parentName, 'sort_order' => $sort++, 'is_active' => true, 'show_in_menu' => true]
            );

            $childSort = 0;
            foreach ($children as $childName) {
                Category::firstOrCreate(
                    ['slug' => str()->slug($parentName . '-' . $childName)],
                    [
                        'name' => $childName,
                        'parent_id' => $parent->id,
                        'sort_order' => $childSort++,
                        'is_active' => true,
                        'show_in_menu' => true,
                    ]
                );
            }
        }

        // Güven/diyet etiketleri.
        $tags = [
            ['name' => 'Organik', 'color' => '#16a34a'],
            ['name' => 'Pestisit Analizli', 'color' => '#0ea5e9'],
            ['name' => 'Vegan', 'color' => '#65a30d'],
            ['name' => 'Glütensiz', 'color' => '#d97706'],
            ['name' => 'Laktozsuz', 'color' => '#7c3aed'],
            ['name' => 'Yerli Üretim', 'color' => '#dc2626'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['slug' => str()->slug($tag['name'])],
                ['name' => $tag['name'], 'color' => $tag['color'], 'is_filterable' => true]
            );
        }
    }
}
