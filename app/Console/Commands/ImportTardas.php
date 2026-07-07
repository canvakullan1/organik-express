<?php

namespace App\Console\Commands;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Tardaş Egenin kataloğunu içe aktarır (Hepsiburada fiyatı + satis.tardas.com.tr görselleri).
 *
 * Veri (repoda):
 *  - database/data/tardas-raw.json  → slug/fiyat/görsel
 *  - database/data/tardas/*.json    → SEO metinleri (MEVCUT kategorilere; category=slug)
 *
 * Kategoriler oluşturulmaz; slug'a göre mevcut kategori bulunur (bulunamazsa ürün kategorisiz).
 *
 * Kullanım:
 *   php artisan import:tardas
 *   php artisan import:tardas --skip-images
 *   php artisan import:tardas --status=draft     # pasif (satışta değil)
 *   php artisan import:tardas --limit=15         # bu çağrıda kaç ürünün görseli inecek
 */
class ImportTardas extends Command
{
    protected $signature = 'import:tardas {--skip-images} {--only=} {--status=active} {--limit=0} {--reimages}';

    protected $description = 'Tardaş Egenin kataloğunu (HB fiyatı + görselleri) içe aktarır';

    public function handle(): int
    {
        $rawPath = database_path('data/tardas-raw.json');
        $partsDir = database_path('data/tardas');

        if (! is_file($rawPath) || ! is_dir($partsDir)) {
            $this->error('Veri bulunamadı (tardas-raw.json / tardas/ klasörü).');

            return self::FAILURE;
        }

        $raw = json_decode(file_get_contents($rawPath), true);

        $catalog = ['products' => []];
        foreach (glob($partsDir . '/*.json') as $part) {
            $data = json_decode(file_get_contents($part), true);
            if (! is_array($data)) {
                $this->warn('Geçersiz JSON atlandı: ' . basename($part));

                continue;
            }
            foreach ($data['products'] ?? [] as $p) {
                $catalog['products'][] = $p;
            }
        }

        $rawBySlug = [];
        foreach ($raw['products'] ?? [] as $p) {
            $rawBySlug[$p['slug']] = $p;
        }

        $status = $this->option('status') === 'draft' ? ProductStatus::Draft->value : ProductStatus::Active->value;
        $only = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));

        if ($this->option('reimages')) {
            $slugs = array_column($catalog['products'] ?? [], 'slug');
            $ids = Product::whereIn('slug', $slugs)->pluck('id');
            $del = ProductImage::whereIn('product_id', $ids)->delete();
            $this->info("Görsel kaydı silindi (yeniden indirilecek): {$del}");
        }

        // MEVCUT kategorileri slug -> id ile çöz (yeni kategori oluşturma)
        $catIdBySlug = Category::pluck('id', 'slug')->all();

        $created = $updated = $missing = $noCat = $imgCount = 0;
        $limit = (int) $this->option('limit');
        $imgProducts = 0;
        $remaining = 0;

        foreach ($catalog['products'] ?? [] as $p) {
            if ($only && ! in_array($p['source_slug'], $only, true)) {
                continue;
            }

            $src = $rawBySlug[$p['source_slug']] ?? null;
            if (! $src) {
                $this->warn("Ham veri yok, atlandı: {$p['source_slug']}");
                $missing++;

                continue;
            }

            $catId = $catIdBySlug[$p['category']] ?? null;
            if (! $catId) {
                $noCat++;
            }

            $existed = Product::where('slug', $p['slug'])->exists();

            $product = Product::updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'category_id' => $catId,
                    'name' => $p['name'],
                    'sku' => $src['sku'] ?? null,
                    'short_description' => $p['short_description'] ?? null,
                    'description' => $p['description'] ?? null,
                    'storage_info' => $p['storage_info'] ?? null,
                    'ingredients' => $p['ingredients'] ?? null,
                    'meta_title' => $p['meta_title'] ?? null,
                    'meta_description' => $p['meta_description'] ?? null,
                    'tax_rate' => $p['tax_rate'] ?? 1,
                    'status' => $status,
                    'is_new' => true,
                ],
            );

            ProductVariant::updateOrCreate(
                ['product_id' => $product->id, 'name' => $p['variant_name'] ?? 'Standart'],
                [
                    'unit' => $p['unit'] ?? 'adet',
                    'unit_amount' => $p['unit_amount'] ?? 1,
                    'price' => $src['price'] ?? 0,
                    'stock' => 100,
                    'track_stock' => false,
                    'is_weight_based' => $p['is_weight_based'] ?? false,
                    'is_default' => true,
                    'is_active' => true,
                ],
            );

            if (! $this->option('skip-images') && $product->images()->count() === 0 && ! empty($src['images'])) {
                if ($limit && $imgProducts >= $limit) {
                    $remaining++;
                } else {
                    $got = false;
                    foreach (array_slice($src['images'], 0, 4) as $idx => $url) {
                        $stored = $this->downloadImage($url, $p['slug'], $idx);
                        if ($stored) {
                            ProductImage::create([
                                'product_id' => $product->id,
                                'path' => $stored,
                                'alt' => $p['name'],
                                'sort_order' => $idx,
                            ]);
                            $imgCount++;
                            $got = true;
                        }
                    }
                    if ($got) {
                        $imgProducts++;
                    }
                }
            }

            $existed ? $updated++ : $created++;
        }

        $this->info("Ürün: {$created} yeni, {$updated} güncellendi, {$missing} eksik, {$noCat} kategorisiz. Görsel: {$imgCount}. Kalan görselsiz-parti: {$remaining}.");

        return self::SUCCESS;
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
            $this->warn("Görsel indirilemedi ({$slug}): " . $e->getMessage());

            return null;
        }
    }
}
