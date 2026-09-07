<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * database/data/removed-images/<liste>.txt içindeki dosya adlarını prod
 * storage diskinden GERÇEKTEN siler.
 *
 * SEBEP: `.cpanel.yml` deploy'u storage'ı `cp -R` ile ÜZERİNE yazar, hiçbir
 * zaman silmez (admin yüklemeleri korunsun diye kasıtlı). Bir kaynağın JSON
 * dosyasını repodan kaldırmak (`git rm`) prod diskindeki fiziksel dosyaları
 * SİLMEZ — dosyalar orada kalır ve URL ile hâlâ erişilebilir olur.
 *
 * Güvenlik: hâlâ bir ProductImage kaydı tarafından kullanılan dosya
 * (korunan/paylaşılan ürünler) ATLANIR — yanlışlıkla başka bir kaynağın
 * görselini silmeyelim.
 *
 *   php artisan catalog:purge-image-files rayaorganik --dry-run
 *   php artisan catalog:purge-image-files rayaorganik
 */
class PurgeImageFiles extends Command
{
    protected $signature = 'catalog:purge-image-files {list} {--dry-run}';

    protected $description = 'Bir liste dosyasındaki görselleri prod diskinden kalıcı siler (hâlâ kullanılanlar hariç)';

    public function handle(): int
    {
        $name = preg_replace('/[^a-z0-9_-]/', '', strtolower($this->argument('list')));
        $file = database_path("data/removed-images/{$name}.txt");

        if (! is_file($file)) {
            $this->error("Liste yok: database/data/removed-images/{$name}.txt");

            return self::FAILURE;
        }

        $wanted = collect(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->unique()
            ->values();

        // Hâlâ kullanılan (herhangi bir ürüne ait, silinmiş dahil) dosya adları — dokunma.
        $inUse = ProductImage::withoutGlobalScopes()->pluck('path')
            ->map(fn ($p) => basename((string) $p))
            ->flip()
            ->all();

        $deleted = 0;
        $skippedInUse = 0;
        $notFound = 0;

        foreach ($wanted as $fname) {
            $path = 'products/' . $fname;
            if (isset($inUse[$fname])) {
                $skippedInUse++;
                $this->line("  kullanımda, atlandı: {$fname}");

                continue;
            }
            if (! Storage::disk('public')->exists($path)) {
                $notFound++;

                continue;
            }
            $this->line(($this->option('dry-run') ? '[DRY] ' : '') . "sil: {$fname}");
            if (! $this->option('dry-run')) {
                Storage::disk('public')->delete($path);
                $deleted++;
            }
        }

        $label = $this->option('dry-run') ? '[DRY-RUN] ' : '';
        $this->info("{$label}Liste: {$wanted->count()} | Silinen: {$deleted} | Kullanımda (korunan): {$skippedInUse} | Zaten yok: {$notFound}");

        return self::SUCCESS;
    }
}
