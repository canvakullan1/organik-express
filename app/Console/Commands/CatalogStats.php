<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Katalog teşhis raporu: durum dağılımı + catalog2 kaynak bazlı sayım.
 * Prod'da DB'ye bakamadığımız için hızlı doğrulama aracı.
 *
 *   php artisan catalog:stats
 *   php artisan catalog:stats --source=elta-ada
 */
class CatalogStats extends Command
{
    protected $signature = 'catalog:stats {--source=}';

    protected $description = 'Ürün durum/kaynak dağılımını raporlar';

    public function handle(): int
    {
        $this->line('PHP memory_limit (Laravel bootstrap sonrasi): ' . ini_get('memory_limit'));
        $this->line('TOPLAM (silinmemiş): ' . Product::count());
        $this->line('  aktif  : ' . Product::where('status', 'active')->count());
        $this->line('  taslak : ' . Product::where('status', 'draft')->count());
        $this->line('  silinmiş (soft): ' . Product::onlyTrashed()->count());

        // Kategori durumu: pasif ama urunu olan kategoriler siteden erisilemez (404).
        $this->line('');
        $this->line('KATEGORILER (urunu olup pasif olanlar <<< ile isaretli):');
        foreach (\App\Models\Category::withCount('products')->orderBy('slug')->get() as $c) {
            $flag = ($c->products_count > 0 && (! $c->is_active || ! $c->show_in_menu)) ? '  <<< ERISILEMEZ' : '';
            if ($c->products_count > 0 || $flag) {
                $this->line(sprintf('  %-26s urun:%4d  aktif:%s  menu:%s%s',
                    $c->slug, $c->products_count,
                    $c->is_active ? 'E' : 'H', $c->show_in_menu ? 'E' : 'H', $flag));
            }
        }
        $this->line('');

        $dir = database_path('data/catalog2');
        $only = trim((string) $this->option('source'));

        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $src = basename($file, '.json');
            if ($only !== '' && $src !== $only) {
                continue;
            }
            $data = json_decode((string) file_get_contents($file), true);
            $slugs = array_values(array_filter(array_column($data['products'] ?? [], 'slug')));
            if (! $slugs) {
                continue;
            }
            $found = Product::withTrashed()->whereIn('slug', $slugs)->count();
            $active = Product::where('status', 'active')->whereIn('slug', $slugs)->count();
            $draft = Product::where('status', 'draft')->whereIn('slug', $slugs)->count();
            $trashed = Product::onlyTrashed()->whereIn('slug', $slugs)->count();
            $missing = count($slugs) - $found;

            $this->line(sprintf(
                '%-22s json:%4d  db:%4d  aktif:%4d  taslak:%4d  silinmis:%3d  EKSIK:%3d',
                $src, count($slugs), $found, $active, $draft, $trashed, $missing
            ));

            if ($only !== '' && $missing > 0) {
                $have = Product::withTrashed()->whereIn('slug', $slugs)->pluck('slug')->all();
                foreach (array_slice(array_diff($slugs, $have), 0, 10) as $s) {
                    $this->warn('   eksik slug: ' . $s);
                }
            }
        }

        return self::SUCCESS;
    }
}
