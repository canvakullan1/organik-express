<?php

namespace App\Console\Commands;

use App\Models\Producer;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * catalog2/<source>.json'daki "producer" alanını, o dosyadaki ürünlerden
 * ŞU AN üretici ATANMAMIŞ olanlara bağlar. SADECE producer_id'yi değiştirir —
 * isim/fiyat/açıklama gibi elle güncellenmiş hiçbir alana dokunmaz.
 *
 *   php artisan catalog2:backfill-producer beyorganik
 *   php artisan catalog2:backfill-producer beyorganik --dry-run
 */
class BackfillProducer extends Command
{
    protected $signature = 'catalog2:backfill-producer {source} {--dry-run}';

    protected $description = 'catalog2 kaynağındaki producer alanını, atanmamış ürünlere geriye dönük bağlar';

    public function handle(): int
    {
        $source = $this->argument('source');
        $file = database_path("data/catalog2/{$source}.json");
        if (! is_file($file)) {
            $this->error("Dosya yok: {$file}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (empty($data['producer'])) {
            $this->error("Bu kaynak dosyasında 'producer' alanı yok.");

            return self::FAILURE;
        }

        $slugs = array_values(array_filter(array_column($data['products'] ?? [], 'slug')));
        $producer = Producer::firstOrCreate(['name' => $data['producer']], ['is_active' => true]);

        $target = Product::withTrashed()->whereIn('slug', $slugs)->whereNull('producer_id');
        $count = $target->count();

        $this->line("Kaynak: {$source} | Üretici: {$producer->name} (#{$producer->id})");
        $this->line("Üreticisi boş olan eşleşen ürün: {$count} / " . count($slugs));

        if ($this->option('dry-run')) {
            $this->info('[DRY-RUN] Kaydedilmedi.');

            return self::SUCCESS;
        }

        $updated = $target->update(['producer_id' => $producer->id]);
        $this->info("{$updated} ürüne üretici bağlandı.");

        return self::SUCCESS;
    }
}
