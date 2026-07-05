<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Sertifikaları database/data/certificates.json'dan içe aktarır.
 *  - group=standart  : renkli rozet (SVG) üretilir
 *  - group=tedarikci : gerçek belge görseli URL'den indirilir
 *
 *   php artisan certificates:seed [--reimages] [--prune]
 */
class SeedCertificates extends Command
{
    protected $signature = 'certificates:seed {--reimages : Görselleri yeniden üret/indir} {--prune : JSON dışındaki (demo) sertifikaları sil}';

    protected $description = 'Sertifikalar sayfasını (standartlar + üretici/tedarikçi belgeleri) JSON verisinden kurar';

    public function handle(): int
    {
        $path = database_path('data/certificates.json');
        if (! is_file($path)) {
            $this->error("Veri dosyası yok: {$path}");

            return self::FAILURE;
        }

        $items = json_decode((string) file_get_contents($path), true);
        if (! is_array($items)) {
            $this->error('certificates.json çözümlenemedi (geçersiz JSON).');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $images = 0;
        $failed = [];

        foreach ($items as $it) {
            $exists = Certificate::where('name', $it['name'])->where('label', $it['label'] ?? null)->exists();

            $cert = Certificate::updateOrCreate(
                ['name' => $it['name'], 'label' => $it['label'] ?? null],
                [
                    'group' => $it['group'] ?? 'standart',
                    'description' => $it['description'] ?? null,
                    'valid_until' => $it['valid_until'] ?? null,
                    'sort_order' => $it['sort_order'] ?? 0,
                    'is_active' => true,
                ],
            );

            $exists ? $updated++ : $created++;

            $needImage = $this->option('reimages') || blank($cert->image);
            if ($needImage) {
                $rel = null;

                if (($it['group'] ?? '') === 'standart' && ! empty($it['badge_color'])) {
                    // Standart rozeti (SVG üret)
                    $rel = 'certificates/' . $it['key'] . '.svg';
                    $svg = $this->badge($it['label'] ?? $it['name'], $it['badge_color']);
                    $this->putBoth($rel, $svg);
                    $images++;
                } elseif (! empty($it['image_url'])) {
                    // Gerçek belge görseli (indir)
                    $data = $this->download($it['image_url'], $it['referer'] ?? null);
                    if ($data !== null) {
                        $ext = str_contains(strtolower($it['image_url']), '.png') ? 'png' : 'jpg';
                        $rel = 'certificates/' . $it['key'] . '.' . $ext;
                        $this->putBoth($rel, $data);
                        $images++;
                    } else {
                        $failed[] = $it['key'];
                    }
                }

                if ($rel) {
                    $cert->image = $rel;
                    $cert->save();
                }
            }

            // Opsiyonel PDF belgesi
            if (! empty($it['pdf_url']) && ($this->option('reimages') || blank($cert->file))) {
                $pdf = $this->download($it['pdf_url'], $it['referer'] ?? null);
                if ($pdf !== null) {
                    $relPdf = 'certificates/' . $it['key'] . '.pdf';
                    $this->putBoth($relPdf, $pdf);
                    $cert->file = $relPdf;
                    $cert->save();
                } else {
                    $failed[] = $it['key'] . '(pdf)';
                }
            }
        }

        // --prune: JSON'da olmayan (kurulumdan kalan demo) sertifikaları sil
        if ($this->option('prune')) {
            $keep = array_map(fn ($c) => $c['name'] . '|' . ($c['label'] ?? ''), $items);
            $pruned = 0;
            foreach (Certificate::all() as $c) {
                if (! in_array($c->name . '|' . ($c->label ?? ''), $keep, true)) {
                    if (! empty($c->image)) {
                        Storage::disk('public')->delete($c->image);
                        $repoFile = storage_path('app/public/' . $c->image);
                        if (is_file($repoFile)) {
                            @unlink($repoFile);
                        }
                    }
                    $this->line("  silindi (demo): {$c->name}");
                    $c->delete();
                    $pruned++;
                }
            }
            $this->info("Silinen demo sertifika: {$pruned}");
        }

        $this->info("Sertifika -> yeni: {$created}, güncellenen: {$updated}, görsel: {$images}");
        if ($failed) {
            $this->warn('İndirilemeyen belge görselleri: ' . implode(', ', $failed));
        }
        $this->info('Toplam sertifika (DB): ' . Certificate::count());

        return self::SUCCESS;
    }

    /** Görseli hem repo storage'a (deploy kaynağı) hem servis edilen public diske yazar. */
    private function putBoth(string $rel, string $data): void
    {
        $repoPath = storage_path('app/public/' . $rel);
        @mkdir(dirname($repoPath), 0775, true);
        @file_put_contents($repoPath, $data);
        Storage::disk('public')->put($rel, $data);
    }

    /** URL'den görsel indirir (tarayıcı UA + opsiyonel referer). Başarısızsa null. */
    private function download(string $url, ?string $referer): ?string
    {
        $headers = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36\r\nAccept: image/avif,image/webp,image/*,*/*\r\n";
        if ($referer) {
            $headers .= "Referer: {$referer}\r\n";
        }
        $ctx = stream_context_create(['http' => ['header' => $headers, 'timeout' => 30, 'follow_location' => 1]]);
        $data = @file_get_contents($url, false, $ctx);

        return ($data !== false && strlen($data) > 3000) ? $data : null;
    }

    /** Standart için dairesel rozet SVG'si. */
    private function badge(string $label, string $color): string
    {
        $label = htmlspecialchars(mb_strtoupper($label, 'UTF-8'), ENT_QUOTES);
        $size = mb_strlen($label) > 9 ? 18 : 24;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" viewBox="0 0 240 240">
  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{$color}"/><stop offset="1" stop-color="{$color}" stop-opacity="0.72"/></linearGradient></defs>
  <circle cx="120" cy="120" r="112" fill="#ffffff"/>
  <circle cx="120" cy="120" r="112" fill="none" stroke="{$color}" stroke-width="6" opacity="0.18"/>
  <circle cx="120" cy="120" r="96" fill="url(#g)" opacity="0.10"/>
  <g fill="none" stroke="{$color}" stroke-width="4" opacity="0.55">
    <circle cx="120" cy="120" r="96" stroke-dasharray="2 9" stroke-linecap="round"/>
  </g>
  <path d="M96 120 l16 16 l34 -40" fill="none" stroke="{$color}" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/>
  <text x="120" y="182" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="{$size}" font-weight="700" fill="{$color}">{$label}</text>
</svg>
SVG;
    }
}
