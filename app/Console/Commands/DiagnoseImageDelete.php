<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Görsel silme akışını (ProductImageController::destroy) gerçek veriyle
 * simüle eder / genel tutarsızlıkları tarar.
 *
 *   php artisan images:diagnose            # genel tarama
 *   php artisan images:diagnose --product=123   # o ürünün görsellerini test et
 */
class DiagnoseImageDelete extends Command
{
    protected $signature = 'images:diagnose {--product=}';

    protected $description = 'Görsel silme 404 sorununu teşhis eder';

    public function handle(): int
    {
        if ($pid = $this->option('product')) {
            $p = Product::withoutGlobalScopes()->find($pid);
            if (! $p) {
                $this->error("Ürün #{$pid} bulunamadı (trashed dahil).");

                return self::FAILURE;
            }
            $this->info("Ürün: #{$p->id} {$p->name} (slug={$p->slug}, trashed=" . ($p->trashed() ? 'EVET' : 'hayır') . ')');
            foreach (ProductImage::where('product_id', $p->id)->orderBy('sort_order')->get() as $img) {
                $exists = Storage::disk('public')->exists($img->path);
                $this->line("  image #{$img->id} path={$img->path} disk_var_mi=" . ($exists ? 'EVET' : 'HAYIR <<<'));
            }

            return self::SUCCESS;
        }

        // Genel tarama: product_id'si geçerli bir Product'a karşılık gelmeyen ProductImage'lar.
        $validIds = Product::withoutGlobalScopes()->pluck('id')->flip();
        $all = ProductImage::count();
        $orphanCount = 0;
        $sample = [];

        ProductImage::orderBy('id')->chunk(500, function ($chunk) use ($validIds, &$orphanCount, &$sample) {
            foreach ($chunk as $img) {
                if (! isset($validIds[$img->product_id])) {
                    $orphanCount++;
                    if (count($sample) < 10) {
                        $sample[] = "image #{$img->id} -> product_id={$img->product_id} (YOK)";
                    }
                }
            }
        });

        $this->line("Toplam ProductImage: {$all}");
        $this->line("product_id GEÇERSİZ (ürünü hiç yok — trashed dahil): {$orphanCount}");
        foreach ($sample as $s) {
            $this->warn('  ' . $s);
        }

        // Trashed ürünlere ait ama admin panelinde "aktif" görünen bir çakışma var mı?
        $activeCount = Product::count();
        $trashedCount = Product::onlyTrashed()->count();
        $this->line("Aktif ürün: {$activeCount} | Trashed ürün: {$trashedCount}");

        return self::SUCCESS;
    }
}
