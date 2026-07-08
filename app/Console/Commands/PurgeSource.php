<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Belirli bir kaynaktan (ilk kurulumda çekilen) ürünleri kataloğdan kaldırır.
 *
 * Slug listesi database/data/<source>-raw.json içindeki ürünlerden alınır.
 * Silme SOFT-DELETE'tir (deleted_at) — istenirse geri alınabilir. Görsel/varyant kaydı korunur.
 *
 * İdempotent: eşleşen aktif ürün yoksa 0 siler. Tam-eşleşme yalnız o kaynağın slug'larıyla.
 *
 *   php artisan catalog:purge-source organikgiller
 *   php artisan catalog:purge-source organikgiller --dry-run
 */
class PurgeSource extends Command
{
    protected $signature = 'catalog:purge-source {source} {--dry-run}';

    protected $description = 'Bir kaynağın (raw.json) ürünlerini kataloğdan soft-delete ile kaldırır';

    public function handle(): int
    {
        $source = preg_replace('/[^a-z0-9_-]/', '', strtolower($this->argument('source')));

        // Ürün slug'ları: önce parça klasörü (kürasyonlu SEO slug), yoksa <source>-raw.json.
        $slugs = [];
        $partsDir = database_path("data/{$source}");
        if (is_dir($partsDir)) {
            foreach (glob($partsDir . '/*.json') ?: [] as $part) {
                $d = json_decode((string) file_get_contents($part), true);
                foreach ($d['products'] ?? [] as $p) {
                    if (! empty($p['slug'])) {
                        $slugs[] = $p['slug'];
                    }
                }
            }
        } else {
            $file = database_path("data/{$source}-raw.json");
            if (! is_file($file)) {
                $this->error("Kaynak verisi yok: database/data/{$source}/ veya {$source}-raw.json");

                return self::FAILURE;
            }
            $data = json_decode((string) file_get_contents($file), true);
            $slugs = array_column($data['products'] ?? [], 'slug');
        }
        $slugs = array_values(array_unique(array_filter($slugs)));
        if (! $slugs) {
            $this->warn('Kaynakta slug bulunamadı, işlem yok.');

            return self::SUCCESS;
        }

        $query = Product::whereIn('slug', $slugs);
        $count = $query->count();

        if ($this->option('dry-run')) {
            $this->info("[DRY-RUN] {$source}: {$count} ürün silinecekti (soft-delete). Toplam slug: " . count($slugs));

            return self::SUCCESS;
        }

        $deleted = $query->delete(); // SoftDeletes → deleted_at doldurulur
        $this->info("{$source}: {$deleted} ürün soft-delete edildi (toplam slug: " . count($slugs) . ').');

        return self::SUCCESS;
    }
}
