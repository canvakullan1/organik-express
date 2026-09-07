<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Aynı görsel dosyasını (path) PAYLAŞAN farklı ürünleri ayırır: her ürün
 * kendi fiziksel dosya kopyasını alır. Böylece bir ürünün görselini silmek
 * başka bir ürünün görselini asla etkilemez.
 *
 * Kök sebep (artık düzeltildi, bkz. ImportCatalog2::registerLocalImages):
 * bir ürünün slug'ı başka bir ürünün slug'ının öneki olduğunda ve devamı
 * rakamla başladığında ("...-300-gr" ve "...-300-gr-5li-2.jpg"), eski glob
 * deseni yanlışlıkla eşleşiyordu. Bu komut geçmişte oluşmuş kayıtları temizler.
 *
 *   php artisan images:deduplicate --dry-run
 *   php artisan images:deduplicate
 */
class DeduplicateImages extends Command
{
    protected $signature = 'images:deduplicate {--dry-run}';

    protected $description = 'Birden fazla ürüne bağlı aynı görsel dosyalarını ayırır (her ürüne kendi kopyası)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $groups = DB::table('product_images')
            ->select('path')
            ->groupBy('path')
            ->havingRaw('COUNT(DISTINCT product_id) > 1')
            ->pluck('path');

        if ($groups->isEmpty()) {
            $this->info('Paylaşılan görsel yok.');

            return self::SUCCESS;
        }

        $this->line($groups->count() . ' paylaşılan dosya bulundu.');
        $copied = 0;
        $failed = 0;

        foreach ($groups as $path) {
            $rows = ProductImage::withoutGlobalScopes()
                ->where('path', $path)
                ->orderBy('id')
                ->get();

            // İlk kayıt orijinal dosyanın "sahibi" kalır; sonrakiler kendi kopyasını alır.
            foreach ($rows->slice(1) as $row) {
                $product = Product::withoutGlobalScopes()->find($row->product_id);
                if (! $product) {
                    $this->warn("  atlandı (ürün yok): image #{$row->id}");

                    continue;
                }

                $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
                $newPath = null;
                for ($i = 1; $i <= 9; $i++) {
                    $candidate = "products/{$product->slug}-own-{$i}.{$ext}";
                    if (! Storage::disk('public')->exists($candidate) && ! is_file(storage_path('app/public/' . $candidate))) {
                        $newPath = $candidate;
                        break;
                    }
                }
                if (! $newPath) {
                    $this->error("  yeni ad üretilemedi: image #{$row->id}");
                    $failed++;

                    continue;
                }

                $this->line(($dry ? '[DRY] ' : '') . "kopyala: {$path} -> {$newPath}  (ürün #{$product->id} {$product->name})");

                if ($dry) {
                    continue;
                }

                $bytes = Storage::disk('public')->exists($path)
                    ? Storage::disk('public')->get($path)
                    : (is_file(storage_path('app/public/' . $path)) ? file_get_contents(storage_path('app/public/' . $path)) : null);

                if ($bytes === null) {
                    $this->error("  kaynak dosya bulunamadı: {$path}");
                    $failed++;

                    continue;
                }

                Storage::disk('public')->put($newPath, $bytes);
                file_put_contents(storage_path('app/public/' . $newPath), $bytes);

                ProductImage::withoutGlobalScopes()->where('id', $row->id)->update(['path' => $newPath]);
                $copied++;
            }
        }

        $label = $dry ? '[DRY-RUN] ' : '';
        $this->info("{$label}Kopyalanan/ayrılan görsel: {$copied} | Hata: {$failed}");

        return self::SUCCESS;
    }
}
