<?php

namespace Database\Seeders;

use App\Models\Bundle;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BundleSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::pluck('id', 'name');

        $bundles = [
            [
                'Haftalık Sebze Kutusu', 199.90, 259.90, true, '#5BA832', '#2C6B27',
                'Mevsimin en taze organik sebzeleri, her hafta özenle seçilip paketlenir.',
                [['1 kg Domates', 1], ['1 kg Salatalık', 1], ['500 g Biber', 1], ['1 Demet Maydanoz', 1], ['1 kg Patates', 1]],
            ],
            [
                'Köy Kahvaltı Sepeti', 289.90, 349.90, true, '#E8B43A', '#C47D1A',
                'Köy peyniri, süzme bal, tereyağı ve daha fazlasıyla zengin bir kahvaltı.',
                [['400 g Köy Peyniri', 1], ['450 g Süzme Bal', 1], ['250 g Tereyağı', 1], ['10 Köy Yumurtası', 1], ['200 g Siyah Zeytin', 1]],
            ],
            [
                'Doğal Detoks Kutusu', 169.90, null, false, '#33A6A6', '#1E7274',
                'Yeşillikler ve meyvelerle hafif, dengeli bir detoks kutusu.',
                [['1 kg Elma', 1], ['500 g Limon', 1], ['1 Demet Roka', 1], ['1 Demet Tere', 1], ['250 g Zencefil', 1]],
            ],
        ];

        foreach ($bundles as $i => [$name, $price, $compare, $weekly, $c1, $c2, $desc, $items]) {
            $path = 'bundles/demo-' . Str::slug($name) . '.svg';
            Storage::disk('public')->put($path, $this->svg($c1, $c2));

            $bundle = Bundle::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name, 'price' => $price, 'compare_at_price' => $compare,
                    'is_weekly' => $weekly, 'is_active' => true, 'sort_order' => $i,
                    'short_description' => $desc, 'image' => $path,
                    'description' => '<p>' . $desc . ' Ürünlerimiz kendi tarlalarımızdan ve yetiştirici dostlarımızdan; tamamı sertifikalıdır. Kapınıza ücretsiz teslim edilir.</p>',
                ]
            );

            if ($bundle->items()->count() === 0) {
                foreach ($items as $j => [$label, $qty]) {
                    $productName = explode(' ', $label, 2)[1] ?? $label;
                    $bundle->items()->create([
                        'label' => $label,
                        'product_id' => $products->first(fn ($id, $n) => Str::contains($n, $productName)) ?? null,
                        'quantity' => $qty,
                        'sort_order' => $j,
                    ]);
                }
            }
        }
    }

    private function svg(string $c1, string $c2): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600">
  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{$c1}"/><stop offset="1" stop-color="{$c2}"/></linearGradient></defs>
  <rect width="800" height="600" fill="url(#g)"/>
  <circle cx="640" cy="140" r="170" fill="#ffffff" opacity="0.08"/>
  <circle cx="160" cy="480" r="130" fill="#ffffff" opacity="0.06"/>
  <g transform="translate(400 320)" fill="none" stroke="#ffffff" stroke-width="14" stroke-linecap="round" stroke-linejoin="round" opacity="0.9">
    <path d="M-150 -40 L0 -120 L150 -40 L150 120 L-150 120 Z"/>
    <path d="M-150 -40 L0 40 L150 -40"/>
    <path d="M0 40 L0 120"/>
  </g>
</svg>
SVG;
    }
}
