<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tagByName = Tag::all()->keyBy('name');
        $catBySlug = Category::all()->keyBy('slug');

        // [ad, kategori-slug, [varyantlar...], etiketler, bayraklar]
        // varyant: [ad, unit, unit_amount, price, compare_at_price, stock, weight]
        $items = [
            ['Organik Domates', 'taze-meyve-sebze-sebzeler', [
                ['1 kg', 'kg', 1, 49.90, 59.90, 120, true],
            ], ['Organik', 'Pestisit Analizli'], ['seasonal' => true, 'featured' => true]],

            ['Organik Salatalık', 'taze-meyve-sebze-sebzeler', [
                ['1 kg', 'kg', 1, 39.90, null, 80, true],
            ], ['Organik'], ['seasonal' => true]],

            ['Köy Yumurtası (15li)', 'kahvaltilik-yumurta', [
                ['15 Adet', 'paket', 1, 89.90, 99.90, 60, false],
            ], ['Yerli Üretim'], ['bestseller' => true, 'featured' => true]],

            ['Çiğ Süzme Bal', 'kahvaltilik-bal-recel', [
                ['450 gr', 'gr', 450, 249.90, null, 40, false],
                ['850 gr', 'gr', 850, 429.90, 459.90, 25, false],
            ], ['Organik', 'Yerli Üretim'], ['featured' => true, 'bestseller' => true]],

            ['Erken Hasat Sızma Zeytinyağı', 'zeytin-zeytinyagi-sizma-zeytinyagi', [
                ['1 L', 'paket', 1, 549.90, 629.90, 35, false],
            ], ['Organik', 'Pestisit Analizli'], ['featured' => true, 'new' => true]],

            ['Tam Buğday Köy Unu', 'kuru-gida-un-makarna', [
                ['1 kg', 'kg', 1, 64.90, null, 100, false],
            ], ['Organik', 'Vegan'], ['new' => true]],

            ['Organik Kırmızı Mercimek', 'kuru-gida-baklagiller', [
                ['1 kg', 'kg', 1, 79.90, 89.90, 90, false],
            ], ['Organik', 'Vegan', 'Glütensiz'], ['bestseller' => true]],

            ['Çiğ Badem', 'kuru-gida-kuruyemis', [
                ['250 gr', 'gr', 250, 159.90, null, 50, false],
                ['500 gr', 'gr', 500, 299.90, 329.90, 30, false],
            ], ['Organik', 'Vegan'], ['new' => true, 'featured' => true]],

            ['Eski Kaşar Peyniri', 'kahvaltilik-peynirler', [
                ['400 gr', 'gr', 400, 189.90, null, 45, true],
            ], ['Yerli Üretim'], ['bestseller' => true]],

            ['Yeşil Kırma Zeytin', 'zeytin-zeytinyagi-yesil-zeytin', [
                ['500 gr', 'gr', 500, 119.90, 139.90, 70, false],
            ], ['Organik'], ['seasonal' => true]],

            ['Organik Maydanoz', 'taze-meyve-sebze-yesillikler', [
                ['1 Demet', 'demet', 1, 19.90, null, 200, false],
            ], ['Organik', 'Pestisit Analizli'], ['seasonal' => true]],

            ['Doğal Sıvı Sabun', 'temizlik-yuzey-temizligi', [
                ['500 ml', 'paket', 1, 74.90, 84.90, 60, false],
            ], ['Vegan'], ['new' => true]],
        ];

        $sort = 0;
        foreach ($items as [$name, $catSlug, $variants, $tags, $flags]) {
            $category = $catBySlug->get($catSlug);
            if (! $category) {
                continue;
            }

            $product = Product::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'category_id' => $category->id,
                    'short_description' => $name . ' — sertifikalı, taze ve üreticisi belli.',
                    'description' => '<p>' . $name . ' ürünümüz özenle seçilmiş üreticilerden, doğal yöntemlerle üretilmiştir. Sertifikalı ve izlenebilirdir.</p>',
                    'storage_info' => "Serin ve kuru yerde saklayınız.\nAçıldıktan sonra buzdolabında muhafaza ediniz.",
                    'ingredients' => '%100 doğal içerik. Katkı maddesi içermez.',
                    'tax_rate' => 1,
                    'status' => 'active',
                    'is_featured' => $flags['featured'] ?? false,
                    'is_seasonal' => $flags['seasonal'] ?? false,
                    'is_new' => $flags['new'] ?? false,
                    'sort_order' => $sort++,
                    'estimated_delivery' => '1-2 iş günü içinde teslim',
                    'certificate_no' => 'TR-ORG-' . str_pad((string) ($sort + 1000), 6, '0', STR_PAD_LEFT),
                ]
            );

            // Varyantlar
            foreach ($variants as $i => [$vName, $unit, $amount, $price, $compare, $stock, $weight]) {
                $product->variants()->firstOrCreate(
                    ['name' => $vName, 'product_id' => $product->id],
                    [
                        'unit' => $unit,
                        'unit_amount' => $amount,
                        'price' => $price,
                        'compare_at_price' => $compare,
                        'stock' => $stock,
                        'track_stock' => true,
                        'is_weight_based' => $weight,
                        'is_default' => $i === 0,
                        'is_active' => true,
                        'sort_order' => $i,
                    ]
                );
            }

            // Etiketler
            $tagIds = collect($tags)->map(fn ($t) => $tagByName->get($t)?->id)->filter()->all();
            $product->tags()->syncWithoutDetaching($tagIds);
        }
    }
}
