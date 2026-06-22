<?php

namespace Database\Seeders;

use App\Models\Producer;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProducerSeeder extends Seeder
{
    public function run(): void
    {
        $producers = [
            ['Ege Zeytin Bahçeleri', 'İzmir, Ege', '#8A9A2B', '#55611C', 'Üç kuşaktır erken hasat naturel sızma zeytinyağı üretiyoruz.'],
            ['Toros Çiftliği', 'Antalya', '#5BA832', '#2C6B27', 'Pestisit kullanmadan, mevsiminde sebze-meyve yetiştiriyoruz.'],
            ['Anadolu Arıcılık', 'Muğla', '#E8B43A', '#C47D1A', 'Yüksek yaylalarda, katkısız süzme bal ve arı ürünleri.'],
            ['Bereket Süt & Peynir', 'Balıkesir', '#33A6A6', '#1E7274', 'Mera sütünden, geleneksel yöntemlerle peynir ve tereyağı.'],
        ];

        $created = [];
        foreach ($producers as $i => [$name, $location, $c1, $c2, $desc]) {
            $path = 'producers/demo-' . Str::slug($name) . '.svg';
            Storage::disk('public')->put($path, $this->svg($c1, $c2));

            $p = Producer::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name, 'location' => $location, 'short_description' => $desc,
                    'story' => '<p>' . $desc . ' Ürünlerimiz sertifikalı ve izlenebilirdir; tarladan/çiftlikten sofranıza şeffaf bir tedarik zinciriyle ulaşır.</p><p>Doğaya saygılı üretim ilkemizdir.</p>',
                    'image' => $path, 'sort_order' => $i, 'is_active' => true,
                ]
            );
            $created[] = $p;
        }

        // Mevcut ürünleri üreticilere dağıt
        Product::whereNull('producer_id')->get()->each(function ($product, $idx) use ($created) {
            $product->update(['producer_id' => $created[$idx % count($created)]->id]);
        });
    }

    private function svg(string $c1, string $c2): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">
  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{$c1}"/><stop offset="1" stop-color="{$c2}"/></linearGradient></defs>
  <rect width="800" height="500" fill="url(#g)"/>
  <circle cx="640" cy="110" r="160" fill="#ffffff" opacity="0.08"/>
  <circle cx="160" cy="420" r="120" fill="#ffffff" opacity="0.06"/>
  <g transform="translate(400 260)" fill="none" stroke="#ffffff" stroke-width="10" stroke-linecap="round" opacity="0.85">
    <path d="M-120 60 h240 M-120 60 v-90 l120 -70 l120 70 v90"/>
    <path d="M-40 60 v-70 h80 v70"/>
  </g>
</svg>
SVG;
    }
}
