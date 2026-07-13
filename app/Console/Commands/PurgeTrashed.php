<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Soft-delete edilmiş (çöpteki) ürünleri KALICI olarak siler.
 *
 * Bu sitedeki tek soft-delete kaynağı organikgiller purge'üdür → çöptekiler
 * organikgiller ürünleridir. Aktif meyve-sebze ürünleri (restore edildi) çöpte
 * DEĞİL, dokunulmaz. Ürünle birlikte görsel + varyant kayıtları da silinir.
 *
 *   php artisan catalog:purge-trashed --dry-run   # ne silinecek raporu
 *   php artisan catalog:purge-trashed             # KALICI sil
 */
class PurgeTrashed extends Command
{
    protected $signature = 'catalog:purge-trashed {--dry-run}';

    protected $description = 'Çöpteki (soft-deleted) organikgiller ürünlerini kalıcı siler (görsel+varyant dahil)';

    public function handle(): int
    {
        $q = Product::onlyTrashed();
        $total = (clone $q)->count();
        $this->info("Çöpteki ürün: {$total}");

        if ($total === 0) {
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            (clone $q)->limit(15)->get()->each(function ($p) {
                $this->line("  #{$p->id} [{$p->slug}] cat:" . ($p->category_id ?? '-') . " — {$p->name}");
            });
            $this->warn('[DRY-RUN] Silinmedi. Kalıcı silmek için --dry-run olmadan çalıştırın.');

            return self::SUCCESS;
        }

        $deleted = 0;
        (clone $q)->chunkById(100, function ($rows) use (&$deleted) {
            foreach ($rows as $p) {
                $p->images()->delete();
                $p->variants()->delete();
                $p->forceDelete();
                $deleted++;
            }
        });

        $this->info("Kalıcı silindi: {$deleted} ürün (görsel+varyant dahil).");

        return self::SUCCESS;
    }
}
