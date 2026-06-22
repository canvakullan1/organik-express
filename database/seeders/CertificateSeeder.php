<?php

namespace Database\Seeders;

use App\Models\Certificate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateSeeder extends Seeder
{
    public function run(): void
    {
        $certs = [
            ['ECOCERT Organik Tarım Sertifikası', 'Organik', '#3f8f2e', 'Uluslararası geçerliliğe sahip organik tarım sertifikası. Ürünlerimizin organik üretim standartlarına uygunluğunu belgeler.'],
            ['ISO 9001:2015 Kalite Yönetim Sistemi', 'ISO 9001', '#2b6cb0', 'Kalite yönetim sistemimizin uluslararası standartlara uygunluğunu gösterir.'],
            ['ISO 22000 Gıda Güvenliği Yönetimi', 'ISO 22000', '#b45a34', 'Gıda güvenliği yönetim sistemimizin belgelenmesi.'],
            ['Helal Gıda Sertifikası', 'Helal', '#1e7274', 'Ürünlerimizin helal gıda kriterlerine uygunluğunu belgeler.'],
        ];

        foreach ($certs as $i => [$name, $label, $color, $desc]) {
            $path = 'certificates/demo-' . Str::slug($label) . '.svg';
            Storage::disk('public')->put($path, $this->svg($label, $color));

            Certificate::firstOrCreate(
                ['name' => $name],
                [
                    'label' => $label, 'description' => $desc, 'image' => $path,
                    'valid_until' => now()->addYears(2)->startOfYear(),
                    'sort_order' => $i, 'is_active' => true,
                ]
            );
        }
    }

    private function svg(string $label, string $color): string
    {
        $short = Str::limit(strtoupper($label), 10, '');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" viewBox="0 0 240 240">
  <circle cx="120" cy="120" r="110" fill="none" stroke="{$color}" stroke-width="8"/>
  <circle cx="120" cy="120" r="92" fill="{$color}" opacity="0.08"/>
  <path d="M95 122 l18 18 l34 -40" fill="none" stroke="{$color}" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/>
  <text x="120" y="185" text-anchor="middle" font-family="Arial, sans-serif" font-size="22" font-weight="700" fill="{$color}">{$short}</text>
</svg>
SVG;
    }
}
