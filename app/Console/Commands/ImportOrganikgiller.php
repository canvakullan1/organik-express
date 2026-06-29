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
 * organikgiller.com kataloğunu (elle yazılmış SEO içerikli) içe aktarır.
 *
 * Veri kaynakları (her ikisi de repoda):
 *  - database/data/organikgiller-raw.json      → kaynaktan çekilen fiyat/görsel/SKU
 *  - database/data/organikgiller-catalog.json  → elle yazılan kategori + SEO metinleri
 *
 * Kullanım:
 *   php artisan import:organikgiller                 # hepsini içe aktar
 *   php artisan import:organikgiller --skip-images   # görselleri indirme
 *   php artisan import:organikgiller --only=slug-a,slug-b
 */
class ImportOrganikgiller extends Command
{
    protected $signature = 'import:organikgiller {--skip-images} {--only=} {--status=active} {--limit=0} {--reimages}';

    protected $description = 'organikgiller.com kataloğunu SEO içerikli olarak içe aktarır';

    public function handle(): int
    {
        $rawPath = database_path('data/organikgiller-raw.json');
        $partsDir = database_path('data/organikgiller');

        if (! is_file($rawPath) || ! is_dir($partsDir)) {
            $this->error('Veri bulunamadı (organikgiller-raw.json / organikgiller/ parça klasörü).');

            return self::FAILURE;
        }

        $raw = json_decode(file_get_contents($rawPath), true);

        // Parça dosyalarını birleştir (her biri {categories:[...], products:[...]})
        $catalog = ['categories' => [], 'products' => []];
        foreach (glob($partsDir . '/*.json') as $part) {
            $data = json_decode(file_get_contents($part), true);
            if (! is_array($data)) {
                $this->warn('Geçersiz JSON atlandı: ' . basename($part));

                continue;
            }
            foreach ($data['categories'] ?? [] as $c) {
                $catalog['categories'][] = $c;
            }
            foreach ($data['products'] ?? [] as $p) {
                $catalog['products'][] = $p;
            }
        }

        // Kategorileri slug bazında tekille (parçalarda tekrar edebilir)
        $catalog['categories'] = array_values(
            collect($catalog['categories'])->keyBy('slug')->all()
        );

        // Ham veriyi source slug ile indeksle
        $rawBySlug = [];
        foreach ($raw['products'] ?? [] as $p) {
            $rawBySlug[$p['slug']] = $p;
        }

        $status = $this->option('status') === 'draft' ? ProductStatus::Draft->value : ProductStatus::Active->value;
        $only = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));

        // --reimages: bu kataloğa ait ürünlerin görsel kayıtlarını sil ki yeniden indirilsin
        if ($this->option('reimages')) {
            $slugs = array_column($catalog['products'] ?? [], 'slug');
            $ids = Product::whereIn('slug', $slugs)->pluck('id');
            $del = ProductImage::whereIn('product_id', $ids)->delete();
            $this->info("Görsel kaydı silindi (yeniden indirilecek): {$del}");
        }

        // 1) Kategoriler
        $catIdBySlug = [];
        foreach ($catalog['categories'] ?? [] as $i => $c) {
            $cat = Category::updateOrCreate(
                ['slug' => $c['slug']],
                [
                    'name' => $c['name'],
                    'description' => $c['description'] ?? null,
                    'meta_title' => $c['meta_title'] ?? null,
                    'meta_description' => $c['meta_description'] ?? null,
                    'sort_order' => $c['sort_order'] ?? $i,
                    'is_active' => true,
                    'show_in_menu' => true,
                ],
            );
            $catIdBySlug[$c['slug']] = $cat->id;
        }
        $this->info(count($catIdBySlug) . ' kategori hazır.');

        // 2) Ürünler
        $created = $updated = $missing = $imgCount = 0;
        $limit = (int) $this->option('limit'); // bu çağrıda kaç ürünün görseli indirilsin (0 = sınırsız)
        $imgProducts = 0; // bu çağrıda görseli indirilen ürün sayısı
        $remaining = 0;   // hâlâ görselsiz ürün sayısı

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

            $existed = Product::where('slug', $p['slug'])->exists();

            $product = Product::updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'category_id' => $catIdBySlug[$p['category']] ?? null,
                    'name' => $p['name'],
                    'sku' => $src['sku'] ?? null,
                    'short_description' => $p['short_description'] ?? null,
                    'description' => $p['description'] ?? null,
                    'meta_title' => $p['meta_title'] ?? null,
                    'meta_description' => $p['meta_description'] ?? null,
                    'tax_rate' => $p['tax_rate'] ?? 1,
                    'status' => $status,
                    'is_new' => true,
                ],
            );

            // Varyant (tek, varsayılan) — fiyat ham veriden
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

            // Görseller — sadece görseli olmayan ürünler için, parti limiti dahilinde
            if (! $this->option('skip-images') && $product->images()->count() === 0) {
                if ($limit && $imgProducts >= $limit) {
                    $remaining++; // bu çağrının limiti doldu, sonraki çağrıya kaldı
                } else {
                    $got = false;
                    foreach (array_slice($src['images'] ?? [], 0, 4) as $idx => $url) {
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

        $this->info("Ürün: {$created} yeni, {$updated} güncellendi, {$missing} eksik. Görsel indirildi: {$imgCount}. Kalan görselsiz: {$remaining}.");

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

            // Deploy korumalı konum: repo storage/app/public (git reset --hard untracked'i silmez,
            // deploy bu klasörü public_html/storage'a kopyalar → görseller deploy'larda korunur).
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
