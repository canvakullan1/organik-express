<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * organikgiller içe aktarımı dışındaki (deneme/eski) ürünleri temizler.
 * Korunacak ürünler: database/data/organikgiller/*.json içindeki target slug'lar.
 *
 *   php artisan products:cleanup-test --dry-run   # sadece listele
 *   php artisan products:cleanup-test             # soft-delete
 *   php artisan products:cleanup-test --force     # kalıcı sil (variant+görsel kaydı dahil)
 */
class CleanupTestProducts extends Command
{
    protected $signature = 'products:cleanup-test {--dry-run} {--force}';

    protected $description = 'organikgiller kataloğu dışındaki deneme ürünlerini siler';

    public function handle(): int
    {
        $partsDir = database_path('data/organikgiller');
        if (! is_dir($partsDir)) {
            $this->error('Katalog parça klasörü yok.');

            return self::FAILURE;
        }

        // Korunacak slug'lar
        $keep = [];
        foreach (glob($partsDir . '/*.json') as $part) {
            $data = json_decode(file_get_contents($part), true);
            foreach ($data['products'] ?? [] as $p) {
                $keep[] = $p['slug'];
            }
        }
        $this->info('Korunacak (içe aktarılan) ürün: ' . count($keep));

        $query = Product::whereNotIn('slug', $keep);
        $victims = $query->get(['id', 'name', 'slug']);

        $this->info('Silinecek (deneme) ürün: ' . $victims->count());
        foreach ($victims as $v) {
            $this->line("  #{$v->id}  {$v->name}  ({$v->slug})");
        }

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN: hiçbir şey silinmedi.');

            return self::SUCCESS;
        }

        if ($victims->isEmpty()) {
            $this->info('Silinecek ürün yok.');

            return self::SUCCESS;
        }

        if ($this->option('force')) {
            foreach ($victims as $v) {
                $p = Product::withTrashed()->find($v->id);
                $p->variants()->delete();
                $p->images()->delete();
                $p->forceDelete();
            }
            $this->info($victims->count() . ' ürün KALICI silindi.');
        } else {
            Product::whereIn('id', $victims->pluck('id'))->delete(); // soft delete
            $this->info($victims->count() . ' ürün soft-delete edildi (geri alınabilir).');
        }

        return self::SUCCESS;
    }
}
