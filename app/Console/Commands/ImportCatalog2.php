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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * İkinci dalga harici katalog aktarımı (çok kaynaklı).
 *
 * Veri: database/data/catalog2/<kaynak>.json — her dosya bir kaynak:
 *   { "source": "sadeorganik", "products": [ {slug,name,category,sku,price,unit,unit_amount,
 *     is_weight_based,images:[url...],short_description,description,meta_title,meta_description} ] }
 *
 * Her ürün tek dosyada TAM veriyle gelir (Tardaş'taki raw+parça ayrımı yok).
 * Kategoriler slug ile bulunur; yoksa $catTree'den (ad + üst) oluşturulur.
 * Görseller ürün başına ilk 4 tanesi indirilip hem repo storage'a hem servis diskine yazılır.
 *
 * Kullanım:
 *   php artisan import:catalog2
 *   php artisan import:catalog2 --source=sadeorganik
 *   php artisan import:catalog2 --skip-images
 *   php artisan import:catalog2 --status=draft
 *   php artisan import:catalog2 --limit=40        # bu çağrıda kaç ürünün görseli inecek
 *   php artisan import:catalog2 --reimages        # görsel kayıtlarını silip yeniden indir
 */
class ImportCatalog2 extends Command
{
    protected $signature = 'import:catalog2 {--source=} {--skip-images} {--status=active} {--limit=0} {--reimages}';

    protected $description = 'İkinci dalga harici kataloğu (çok kaynaklı) içe aktarır';

    /**
     * Gerektiğinde oluşturulacak kategoriler: slug => [ad, üst-slug|null, sort].
     * Mevcut slug'lar zaten DB'de varsa DOKUNULMAZ (firstOrCreate).
     */
    private array $catTree = [
        // YENİ kök kategoriler (prod'da yok — oluşturulur, menüde görünür)
        'bal' => ['Bal & Arı Ürünleri', null, 9],
        'bebek' => ['Organik Bebek', null, 40],
        'glutensiz' => ['Glutensiz Ürünler', null, 41],
        // Prod'da MEVCUT olanlar (bulunur, oluşturulmaz). Yerelde yoksa doğru üst ile açılsın:
        'meyve-sebze' => ['Meyve & Sebze', null, 1],
        'taze-meyve' => ['Taze Meyve', 'meyve-sebze', 2],
        'taze-sebze' => ['Taze Sebze', 'meyve-sebze', 3],
        'bakkaliye' => ['Bakkaliye', null, 20],
        'sut-kahvaltilik' => ['Süt & Kahvaltılık', null, 21],
        'bakliyat-makarna' => ['Bakliyat & Makarna', 'bakkaliye', 22],
        'baharat-aktar' => ['Baharat & Aktar', 'bakkaliye', 23],
        'sos-salca-sirke' => ['Sos, Salça & Sirke', 'bakkaliye', 24],
        'kuruyemis-kurutulmus' => ['Kuruyemiş & Kurutulmuş', 'bakkaliye', 25],
        'kahvaltilik-recel' => ['Kahvaltılık & Reçel', 'sut-kahvaltilik', 26],
        'zeytin-zeytinyagi-yag' => ['Zeytin & Zeytinyağı', 'sut-kahvaltilik', 27],
    ];

    public function handle(): int
    {
        $dir = database_path('data/catalog2');
        if (! is_dir($dir)) {
            $this->error('Veri klasörü yok: database/data/catalog2');

            return self::FAILURE;
        }

        $onlySource = trim((string) $this->option('source'));
        $files = glob($dir . '/*.json') ?: [];
        if ($onlySource !== '') {
            $files = array_filter($files, fn ($f) => basename($f, '.json') === $onlySource);
        }
        if (! $files) {
            $this->error('İşlenecek kaynak dosyası bulunamadı.');

            return self::FAILURE;
        }

        $status = $this->option('status') === 'draft' ? ProductStatus::Draft->value : ProductStatus::Active->value;
        $limit = (int) $this->option('limit');

        // reimages: bu partideki ürünlerin görsel kayıtlarını sil
        if ($this->option('reimages')) {
            $slugs = [];
            foreach ($files as $f) {
                $d = json_decode((string) file_get_contents($f), true);
                foreach ($d['products'] ?? [] as $p) {
                    if (! empty($p['slug'])) {
                        $slugs[] = $p['slug'];
                    }
                }
            }
            $ids = Product::whereIn('slug', $slugs)->pluck('id');
            $del = ProductImage::whereIn('product_id', $ids)->delete();
            $this->info("Görsel kaydı silindi (yeniden indirilecek): {$del}");
        }

        $created = $updated = $noCat = $imgCount = $imgProducts = $remaining = 0;

        foreach ($files as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (! is_array($data) || empty($data['products'])) {
                $this->warn('Boş/geçersiz kaynak atlandı: ' . basename($file));

                continue;
            }
            $source = $data['source'] ?? basename($file, '.json');
            $this->line("Kaynak: {$source} (" . count($data['products']) . ' ürün)');

            foreach ($data['products'] as $p) {
                if (empty($p['slug']) || empty($p['name'])) {
                    continue;
                }

                $catId = $this->resolveCategory($p['category'] ?? null);
                if (! $catId) {
                    $noCat++;
                }

                $existed = Product::withTrashed()->where('slug', $p['slug'])->exists();

                $product = Product::withTrashed()->updateOrCreate(
                    ['slug' => $p['slug']],
                    [
                        'category_id' => $catId,
                        'name' => $p['name'],
                        'sku' => $p['sku'] ?? null,
                        'short_description' => $p['short_description'] ?? null,
                        'description' => $p['description'] ?? null,
                        'storage_info' => $p['storage_info'] ?? null,
                        'ingredients' => $p['ingredients'] ?? null,
                        'meta_title' => $p['meta_title'] ?? null,
                        'meta_description' => $p['meta_description'] ?? null,
                        'tax_rate' => $p['tax_rate'] ?? 1,
                        'status' => $status,
                        'is_new' => true,
                        'deleted_at' => null,
                    ],
                );

                ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'name' => $p['variant_name'] ?? 'Standart'],
                    [
                        'unit' => $p['unit'] ?? 'adet',
                        'unit_amount' => $p['unit_amount'] ?? 1,
                        'price' => $p['price'] ?? 0,
                        'stock' => 100,
                        'track_stock' => false,
                        'is_weight_based' => $p['is_weight_based'] ?? false,
                        'is_default' => true,
                        'is_active' => true,
                    ],
                );

                if ($product->images()->count() === 0) {
                    // 1) Repoda commit'li yerel görselleri bağla (indirme yok — deploy dostu)
                    $local = $this->registerLocalImages($product->id, $p['slug'], $p['name']);
                    $imgCount += $local;

                    // 2) Yerel görsel yoksa ve --skip-images verilmediyse URL'den indir
                    if ($local === 0 && ! $this->option('skip-images') && ! empty($p['images'])) {
                        if ($limit && $imgProducts >= $limit) {
                            $remaining++;
                        } else {
                            $got = false;
                            foreach (array_slice($p['images'], 0, 2) as $idx => $url) {
                                $stored = $this->downloadImage((string) $url, $p['slug'], $idx);
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
                }

                $existed ? $updated++ : $created++;
            }
        }

        $this->info("Ürün: {$created} yeni, {$updated} güncellendi, {$noCat} kategorisiz. Görsel: {$imgCount}. Kalan görselsiz-parti: {$remaining}.");

        return self::SUCCESS;
    }

    /** Kategoriyi slug ile bul; yoksa $catTree'den (üst dahil) oluştur. */
    private function resolveCategory(?string $slug): ?int
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return null;
        }

        $cat = Category::where('slug', $slug)->first();
        if ($cat) {
            return $cat->id;
        }

        [$name, $parentSlug, $sort] = $this->catTree[$slug] ?? [Str::title(str_replace('-', ' ', $slug)), null, 50];
        $parentId = $parentSlug ? $this->resolveCategory($parentSlug) : null;

        $cat = Category::create([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parentId,
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => $sort,
            'meta_title' => $name . ' | Organik Express',
            'meta_description' => $name . ' kategorisindeki organik ürünleri keşfedin; katkısız, doğal ve taze kapınızda.',
        ]);

        return $cat->id;
    }

    /** Repoda hazır yerel görselleri (products/{slug}-N.*) DB'ye bağla; indirme yok. */
    private function registerLocalImages(int $productId, string $slug, string $name): int
    {
        $base = storage_path('app/public/products/');
        $files = glob($base . $slug . '-*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
        sort($files);
        $n = 0;
        foreach ($files as $full) {
            ProductImage::create([
                'product_id' => $productId,
                'path' => 'products/' . basename($full),
                'alt' => $name,
                'sort_order' => $n,
            ]);
            $n++;
        }

        return $n;
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
            $body = $res->body();

            $full = storage_path('app/public/' . $path);
            File::ensureDirectoryExists(dirname($full));
            File::put($full, $body);
            Storage::disk('public')->put($path, $body);

            return $path;
        } catch (\Throwable $e) {
            $this->warn("Görsel indirilemedi ({$slug}): " . $e->getMessage());

            return null;
        }
    }
}
