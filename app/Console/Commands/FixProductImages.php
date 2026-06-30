<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Görseli olmayan ürünleri tespit eder ve görsellerini indirir.
 *  - Önce katalog (target slug -> source slug) eşlemesiyle ham veriden,
 *  - Ham veride yoksa kaynak ürün sayfasından taze çekerek.
 *
 *   php artisan products:fix-images --report   # sadece listele
 *   php artisan products:fix-images            # eksik görselleri indir
 */
class FixProductImages extends Command
{
    protected $signature = 'products:fix-images {--report} {--limit=0}';

    protected $description = 'Görseli olmayan ürünleri tespit eder ve indirir';

    public function handle(): int
    {
        // Görseli olmayan ürünler
        $missing = Product::whereDoesntHave('images')->orderBy('id')->get(['id', 'name', 'slug']);
        $this->info('Görseli olmayan ürün: ' . $missing->count());
        foreach ($missing as $m) {
            $this->line("  #{$m->id}  {$m->name}  ({$m->slug})");
        }

        if ($this->option('report') || $missing->isEmpty()) {
            return self::SUCCESS;
        }

        // target slug -> source slug eşlemesi (katalog parçalarından)
        $slugMap = [];
        foreach (glob(database_path('data/organikgiller/*.json')) as $part) {
            $data = json_decode(file_get_contents($part), true);
            foreach ($data['products'] ?? [] as $p) {
                $slugMap[$p['slug']] = $p['source_slug'];
            }
        }

        // source slug -> ham veri (images + source_url)
        $raw = json_decode(file_get_contents(database_path('data/organikgiller-raw.json')), true);
        $rawBySlug = [];
        foreach ($raw['products'] ?? [] as $p) {
            $rawBySlug[$p['slug']] = $p;
        }

        $limit = (int) $this->option('limit');
        $fixed = 0;
        $still = 0;

        foreach ($missing as $m) {
            if ($limit && $fixed >= $limit) {
                break;
            }

            $src = $rawBySlug[$slugMap[$m->slug] ?? ''] ?? null;
            $images = $src['images'] ?? [];

            // Ham veride görsel yoksa kaynak sayfadan taze çek
            if (empty($images) && ! empty($src['source_url'])) {
                $images = $this->scrapeImages($src['source_url']);
            }

            $got = 0;
            foreach (array_slice($images, 0, 4) as $idx => $url) {
                $path = $this->downloadImage($url, $m->slug, $idx);
                if ($path) {
                    ProductImage::create([
                        'product_id' => $m->id,
                        'path' => $path,
                        'alt' => $m->name,
                        'sort_order' => $idx,
                    ]);
                    $got++;
                }
            }

            if ($got > 0) {
                $fixed++;
                $this->info("  ✓ {$m->slug} — {$got} görsel");
            } else {
                $still++;
                $this->warn("  ✗ {$m->slug} — görsel bulunamadı");
            }
        }

        $this->info("Düzeltilen ürün: {$fixed} | Hâlâ görselsiz: {$still}");

        return self::SUCCESS;
    }

    /** Kaynak ürün sayfasından JSON-LD / og:image görsellerini çek. */
    private function scrapeImages(string $url): array
    {
        try {
            $html = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->timeout(30)->get($url)->body();
        } catch (\Throwable $e) {
            return [];
        }

        $images = [];
        if (preg_match_all('/"contentUrl":"([^"]+)"/', $html, $m)) {
            foreach ($m[1] as $u) {
                $images[] = $u;
            }
        }
        if (empty($images) && preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $mm)) {
            $images[] = $mm[1];
        }

        // tekille + yüksek çözünürlük
        $clean = [];
        foreach ($images as $img) {
            $img = preg_replace('#/v1/fit/[^/]+/#', '/v1/fit/w_1280,h_1280,q_90/', $img);
            $key = preg_replace('#/v1/.*$#', '', $img);
            $clean[$key] = $img;
        }

        return array_values($clean);
    }

    private function downloadImage(string $url, string $slug, int $idx): ?string
    {
        try {
            $res = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->timeout(40)->get($url);
            if (! $res->successful()) {
                return null;
            }
            $ext = str_contains($url, '.png') ? 'png' : (str_contains($url, '.webp') ? 'webp' : 'jpg');
            $path = "products/{$slug}-" . ($idx + 1) . ".{$ext}";
            $full = storage_path('app/public/' . $path);
            File::ensureDirectoryExists(dirname($full));
            File::put($full, $res->body());

            return $path;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
