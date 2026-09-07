<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * storage/app/public/products altındaki, hiçbir ürüne (silinmiş olsa bile) ait
 * OLMAYAN görsel dosyalarını temizler.
 *
 * SEBEP: deploy, repo storage'ını prod'a `cp -R` ile ÜZERİNE yazar — hiçbir zaman
 * silmez. Bir ürün silinince (soft-delete ya da kaynak kaldırma) görseli prod
 * diskinde kalır; dosya adı hâlâ eski markayı/tedarikçiyi taşıyabilir
 * (ör. "raya-organik-..." ) ve doğrudan URL ile hâlâ erişilebilir olur.
 *
 *   php artisan catalog:clean-orphan-images --dry-run
 *   php artisan catalog:clean-orphan-images
 */
class CleanOrphanImages extends Command
{
    protected $signature = 'catalog:clean-orphan-images {--dry-run}';

    protected $description = 'Hiçbir ürüne (silinmiş dahil) ait olmayan görsel dosyalarını siler';

    public function handle(): int
    {
        // Silinmiş ürünlerin görselleri de korunsun (geri alma ihtimali) —
        // yalnızca products tablosunda HİÇ karşılığı olmayan dosyalar yetim sayılır.
        $referenced = ProductImage::withoutGlobalScopes()->pluck('path')
            ->map(fn ($p) => basename((string) $p))
            ->filter()
            ->flip()
            ->all();

        $dir = 'products';
        $files = Storage::disk('public')->files($dir);
        $orphans = [];

        foreach ($files as $path) {
            $name = basename($path);
            if (! isset($referenced[$name])) {
                $orphans[] = $path;
            }
        }

        if (! $orphans) {
            $this->info('Yetim görsel yok.');

            return self::SUCCESS;
        }

        foreach ($orphans as $o) {
            $this->line(($this->option('dry-run') ? '[DRY] ' : '') . 'sil: ' . $o);
        }

        if ($this->option('dry-run')) {
            $this->info('[DRY-RUN] ' . count($orphans) . ' yetim görsel silinecekti.');

            return self::SUCCESS;
        }

        // Hem Storage::disk('public') kökünden (prod'da public_html/storage — canlı
        // sunum) hem de storage_path('app/public') altından (deploy'un `cp -R` MERGE
        // KAYNAĞI) sil — yoksa bir sonraki deploy dosyayı diriltir.
        Storage::disk('public')->delete($orphans);
        foreach ($orphans as $o) {
            @unlink(storage_path('app/public/' . $o));
        }
        $this->info(count($orphans) . ' yetim görsel silindi.');

        return self::SUCCESS;
    }
}
