<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Verilen slug'lardaki ürünleri kataloğdan kaldırır (SOFT-delete).
 *
 * `catalog:purge-source` bir kaynak dosyasına dayanır ve başka kaynakta da geçen
 * slug'ları korur. Kaynak dosyası kaldırıldıktan sonra geriye kalan tekil artıklar
 * (ör. eski bir kaynaktan gelip açıklaması hâlâ o markayı taşıyan ürünler) için
 * bu komut kullanılır.
 *
 *   php artisan catalog:purge-slugs "slug-1,slug-2" --dry-run
 */
class PurgeSlugs extends Command
{
    protected $signature = 'catalog:purge-slugs {slugs : Virgülle ayrılmış slug listesi} {--dry-run}';

    protected $description = 'Belirtilen slug\'lardaki ürünleri soft-delete eder';

    public function handle(): int
    {
        $slugs = collect(explode(',', (string) $this->argument('slugs')))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! $slugs) {
            $this->error('Slug verilmedi.');

            return self::FAILURE;
        }

        $found = Product::whereIn('slug', $slugs)->get(['id', 'slug', 'name']);
        $missing = array_diff($slugs, $found->pluck('slug')->all());

        foreach ($found as $p) {
            $this->line(($this->option('dry-run') ? '[DRY] ' : '') . "sil: #{$p->id} {$p->name}");
        }
        foreach ($missing as $m) {
            $this->warn("  bulunamadı (zaten yok/silinmiş): {$m}");
        }

        if ($this->option('dry-run')) {
            $this->info('[DRY-RUN] ' . $found->count() . ' ürün silinecekti.');

            return self::SUCCESS;
        }

        $deleted = Product::whereIn('slug', $slugs)->delete();
        $this->info("{$deleted} ürün soft-delete edildi.");

        return self::SUCCESS;
    }
}
