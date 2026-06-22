<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Demo görseller üretir (SVG) ve hero slider banner'ları + kategori fotoğraflarını kurar.
 * Görseller admin panelinden (Banner/Slider ve Kategoriler) değiştirilebilir.
 * Idempotent: zaten banner varsa slider'ı tekrar oluşturmaz.
 */
class DemoMediaSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHeroBanners();
        $this->seedCategoryImages();
    }

    private function seedHeroBanners(): void
    {
        $slides = [
            ['taze-sebze', 'Taze Mevsim Sebzeleri', 'Bahçeden sofranıza, günlük hasat tazeliği', 'Hemen keşfet', '#5BA832', '#2C6B27'],
            ['zeytinyagi', 'Soğuk Sıkım Zeytinyağı', 'Erken hasat, naturel sızma · analizli', 'Ürünleri gör', '#8A9A2B', '#55611C'],
            ['kahvaltilik', 'Doğal Kahvaltılıklar', 'Köy peyniri, süzme bal, tereyağı', 'Sofranı kur', '#E0A82E', '#B4762A'],
        ];

        $existing = Banner::where('position', 'hero')->exists();

        $sort = 0;
        foreach ($slides as [$key, $title, $subtitle, $btn, $c1, $c2]) {
            $path = "banners/demo-{$key}.svg";
            Storage::disk('public')->put($path, $this->heroSvg($c1, $c2));

            if (! $existing) {
                Banner::create([
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'button_text' => $btn,
                    'image' => $path,
                    'link' => '/arama',
                    'position' => 'hero',
                    'sort_order' => $sort++,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function seedCategoryImages(): void
    {
        // slug parçası => [renk1, renk2, motif]
        $config = [
            'taze-meyve' => ['#6CB73B', '#2E7A2A', 'apple'],
            'kahvaltilik' => ['#F0B43C', '#C9821C', 'honey'],
            'zeytin' => ['#93A52F', '#5A671D', 'olive'],
            'kuru-gida' => ['#C28A4E', '#84582C', 'wheat'],
            'et' => ['#DD5C44', '#A23023', 'fish'],
            'temizlik' => ['#34AEB0', '#1E7274', 'spray'],
            'kisisel-bakim' => ['#A879D6', '#714AA0', 'leaf'],
        ];

        foreach (Category::whereNull('parent_id')->get() as $cat) {
            $cfg = collect($config)->first(fn ($v, $k) => str_contains($cat->slug, $k)) ?? ['#6CB73B', '#2E7A2A', 'leaf'];
            [$c1, $c2, $motif] = $cfg;

            $path = 'categories/demo-' . $cat->slug . '.svg';
            Storage::disk('public')->put($path, $this->categorySvg($c1, $c2, $motif));

            // Demo görseli her zaman tazele; sadece DB yolunu boşsa yaz (kullanıcı yüklemesini ezme)
            if (empty($cat->image)) {
                $cat->update(['image' => $path]);
            }
        }
    }

    /** Geniş hero arka planı — taze ürün kompozisyonu hissi (metin şablonda overlay edilir). */
    private function heroSvg(string $c1, string $c2): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="620" viewBox="0 0 1600 620" preserveAspectRatio="xMidYMid slice">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$c1}"/><stop offset="1" stop-color="{$c2}"/>
    </linearGradient>
    <radialGradient id="glow" cx="0.72" cy="0.45" r="0.55">
      <stop offset="0" stop-color="#ffffff" stop-opacity="0.16"/>
      <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="1600" height="620" fill="url(#bg)"/>
  <rect width="1600" height="620" fill="url(#glow)"/>
  <!-- bokeh -->
  <g fill="#ffffff"><circle cx="1500" cy="90" r="9" opacity="0.25"/><circle cx="1380" cy="60" r="6" opacity="0.18"/><circle cx="1240" cy="120" r="5" opacity="0.15"/><circle cx="1560" cy="300" r="7" opacity="0.2"/></g>
  <!-- taze ürün kümesi (sag) -->
  <g transform="translate(1140 350)">
    <ellipse cx="120" cy="160" rx="430" ry="120" fill="#000000" opacity="0.10"/>
    <!-- yaprak yatak -->
    <g fill="#ffffff" opacity="0.16">
      <path d="M-120 70 C -180 0 -120 -90 -20 -70 C -60 -10 -80 50 -120 70 Z"/>
      <path d="M300 60 C 370 0 340 -100 230 -90 C 280 -20 290 30 300 60 Z"/>
    </g>
    <!-- domates -->
    <circle cx="40" cy="40" r="95" fill="#ffffff" opacity="0.95"/>
    <path d="M40 -55 q -6 -18 -28 -22 q 4 22 28 22 z" fill="#ffffff" opacity="0.7"/>
    <!-- elma -->
    <circle cx="200" cy="70" r="78" fill="#ffffff" opacity="0.85"/>
    <!-- limon/portakal -->
    <circle cx="-70" cy="95" r="64" fill="#ffffff" opacity="0.8"/>
    <!-- üzüm benekleri -->
    <g fill="#ffffff" opacity="0.7"><circle cx="270" cy="-30" r="26"/><circle cx="312" cy="-8" r="26"/><circle cx="252" cy="6" r="26"/><circle cx="294" cy="28" r="26"/></g>
    <!-- yaprak vurgusu -->
    <path d="M120 -90 C 200 -150 300 -130 300 -130 C 270 -60 200 -50 120 -90 Z" fill="#ffffff" opacity="0.6"/>
  </g>
</svg>
SVG;
    }

    /** Kare kategori kartı görseli — kategoriye özel motif (isim kartta ayrıca gösterilir). */
    private function categorySvg(string $c1, string $c2, string $motif): string
    {
        $art = $this->motif($motif, $c2);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$c1}"/><stop offset="1" stop-color="{$c2}"/>
    </linearGradient>
  </defs>
  <rect width="600" height="600" fill="url(#g)"/>
  <circle cx="470" cy="110" r="150" fill="#ffffff" opacity="0.09"/>
  <circle cx="110" cy="500" r="120" fill="#ffffff" opacity="0.07"/>
  <circle cx="300" cy="300" r="170" fill="#ffffff" opacity="0.10"/>
  <g transform="translate(300 300)">{$art}</g>
</svg>
SVG;
    }

    /** Kategoriye özel beyaz, flat illüstrasyon (merkezde, ~±150). */
    private function motif(string $key, string $accent): string
    {
        return match ($key) {
            // Elma / domates — yuvarlak meyve + sap + yaprak
            'apple' => '
                <path d="M-6 -68 q 6 26 6 36" fill="none" stroke="#ffffff" stroke-width="11" stroke-linecap="round"/>
                <path d="M2 -48 c -10 -30 -58 -34 -58 -34 c 2 30 28 42 58 34 z" fill="#ffffff" opacity="0.82"/>
                <path d="M0 -42 c -34 -22 -104 4 -104 64 c 0 50 38 92 70 92 c 16 0 22 -8 34 -8 c 12 0 18 8 34 8 c 32 0 70 -42 70 -92 c 0 -60 -70 -86 -104 -64 z" fill="#ffffff" opacity="0.96"/>',

            // Bal kavanozu — gövde + kapak + etiket + akan bal
            'honey' => '
                <rect x="-78" y="-58" width="156" height="34" rx="14" fill="#ffffff" opacity="0.96"/>
                <path d="M-66 -24 h132 v118 a26 26 0 0 1 -26 26 h-80 a26 26 0 0 1 -26 -26 z" fill="#ffffff" opacity="0.96"/>
                <rect x="-44" y="6" width="88" height="62" rx="10" fill="'.$accent.'" opacity="0.30"/>
                <path d="M-66 -24 q 24 26 0 52 q -24 -26 0 -52z m22 0 q 0 18 0 30" fill="none" stroke="'.$accent.'" stroke-width="0"/>
                <path d="M40 -90 q 10 26 0 40 q -10 -14 0 -40z" fill="#ffffff" opacity="0.7"/>',

            // Zeytin dalı + yağ damlası
            'olive' => '
                <path d="M-120 -70 Q 0 -30 120 -96" fill="none" stroke="#ffffff" stroke-width="11" stroke-linecap="round"/>
                <path d="M78 -96 q 44 -8 66 -42 q -50 -2 -66 42z" fill="#ffffff" opacity="0.8"/>
                <ellipse cx="-44" cy="-18" rx="27" ry="40" fill="#ffffff" opacity="0.96" transform="rotate(-18 -44 -18)"/>
                <ellipse cx="26" cy="-36" rx="27" ry="40" fill="#ffffff" opacity="0.88" transform="rotate(14 26 -36)"/>
                <path d="M0 40 c -26 30 -26 64 0 64 c 26 0 26 -34 0 -64z" fill="#ffffff" opacity="0.95"/>',

            // Buğday başağı
            'wheat' => '
                <path d="M0 140 V -54" fill="none" stroke="#ffffff" stroke-width="11" stroke-linecap="round"/>
                <g fill="#ffffff" opacity="0.95">
                  <path d="M0 -88 c -26 8 -34 40 -10 56 c 24 -16 18 -48 10 -56z"/>
                  <path d="M0 -88 c 26 8 34 40 10 56 c -24 -16 -18 -48 -10 -56z"/>
                  <ellipse cx="-30" cy="-42" rx="20" ry="34" transform="rotate(-32 -30 -42)"/>
                  <ellipse cx="30" cy="-42" rx="20" ry="34" transform="rotate(32 30 -42)"/>
                  <ellipse cx="-34" cy="2" rx="20" ry="34" transform="rotate(-32 -34 2)"/>
                  <ellipse cx="34" cy="2" rx="20" ry="34" transform="rotate(32 34 2)"/>
                  <ellipse cx="-36" cy="46" rx="20" ry="34" transform="rotate(-32 -36 46)"/>
                  <ellipse cx="36" cy="46" rx="20" ry="34" transform="rotate(32 36 46)"/>
                </g>',

            // Balık
            'fish' => '
                <ellipse cx="-12" cy="0" rx="118" ry="66" fill="#ffffff" opacity="0.96"/>
                <path d="M96 0 l 56 -50 v 100 z" fill="#ffffff" opacity="0.96"/>
                <path d="M-12 -66 q 0 -34 30 -40 q -10 24 0 42z" fill="#ffffff" opacity="0.8"/>
                <circle cx="-78" cy="-14" r="11" fill="'.$accent.'"/>',

            // Sprey şişe (temizlik)
            'spray' => '
                <rect x="-46" y="-6" width="92" height="140" rx="20" fill="#ffffff" opacity="0.96"/>
                <rect x="-28" y="-54" width="56" height="50" fill="#ffffff" opacity="0.96"/>
                <path d="M-28 -50 h-46 v22 h32 z" fill="#ffffff" opacity="0.92"/>
                <rect x="-38" y="44" width="76" height="74" rx="12" fill="'.$accent.'" opacity="0.28"/>
                <g fill="#ffffff" opacity="0.85"><circle cx="-92" cy="-58" r="7"/><circle cx="-108" cy="-40" r="6"/><circle cx="-92" cy="-22" r="7"/></g>',

            // Yaprak + damla (doğal bakım)
            default => '
                <path d="M0 130 C -92 64 -92 -64 0 -130 C 92 -64 92 64 0 130 Z" fill="#ffffff" opacity="0.94"/>
                <path d="M0 -120 V 120" stroke="'.$accent.'" stroke-width="9" opacity="0.4" stroke-linecap="round"/>
                <path d="M0 -50 L 54 -88 M0 0 L 60 -34 M0 50 L 54 20" stroke="'.$accent.'" stroke-width="7" opacity="0.35" stroke-linecap="round"/>
                <path d="M0 -50 L -54 -88 M0 0 L -60 -34 M0 50 L -54 20" stroke="'.$accent.'" stroke-width="7" opacity="0.35" stroke-linecap="round"/>',
        };
    }
}
