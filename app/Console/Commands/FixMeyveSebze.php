<?php

namespace App\Console\Commands;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * meyve-sebze.json ürünlerinin CANLIDA görünmesini garanti eder.
 *
 * Prod'da bazı slug'lar önceden vardı (18 güncellendi); import eşleşen kaydı
 * güncelledi ama storefront yanlış/pasif veya duplika bir kayda çözümlenebiliyor
 * → ürün sayfası 404, kategoride görünmüyor. Bu komut her slug için:
 *  - Doğru ürünü (produce kategorisinde olan; yoksa ilk) AKTİF + doğru kategori yapar.
 *  - Aynı slug'lı diğer (duplika) kayıtları slug'ını serbest bırakıp soft-delete eder.
 *
 *   php artisan catalog:fix-meyve-sebze
 *   php artisan catalog:fix-meyve-sebze --dry-run
 */
class FixMeyveSebze extends Command
{
    protected $signature = 'catalog:fix-meyve-sebze {--dry-run}';

    protected $description = 'meyve-sebze ürünlerini aktif + doğru kategori yapar; duplika slug çakışmalarını temizler';

    public function handle(): int
    {
        $file = database_path('data/catalog2/meyve-sebze.json');
        if (! is_file($file)) {
            $this->error('meyve-sebze.json yok.');

            return self::FAILURE;
        }
        $data = json_decode((string) file_get_contents($file), true);
        $dry = (bool) $this->option('dry-run');

        $catCache = [];
        $fixed = 0;
        $deduped = 0;

        foreach ($data['products'] ?? [] as $p) {
            $slug = $p['slug'] ?? null;
            $catSlug = $p['category'] ?? null;
            if (! $slug || ! $catSlug) {
                continue;
            }
            $catId = $catCache[$catSlug] ??= Category::where('slug', $catSlug)->value('id');
            if (! $catId) {
                $this->warn("Kategori yok: {$catSlug}");
                continue;
            }

            $rows = Product::withTrashed()->where('slug', $slug)->orderBy('id')->get();
            if ($rows->isEmpty()) {
                $this->warn("Ürün yok: {$slug}");
                continue;
            }

            $keep = $rows->firstWhere('category_id', $catId) ?? $rows->first();
            $state = $rows->map(fn ($r) => $r->id . '(' . $r->status->value . ($r->trashed() ? ',trashed' : '') . ',cat' . ($r->category_id ?? '-') . ')')->implode(' ');
            $this->line("{$slug}: keep #{$keep->id} | " . $rows->count() . " kayit: {$state}");

            if (! $dry) {
                if ($keep->trashed()) {
                    $keep->restore(); // düzgün un-trash (deleted_at fillable değil)
                }
                $keep->forceFill([
                    'category_id' => $catId,
                    'status' => ProductStatus::Active->value,
                ])->save();
                $fixed++;

                foreach ($rows as $r) {
                    if ($r->id === $keep->id) {
                        continue;
                    }
                    // slug'ı serbest bırak + soft-delete (storefront trashed'i görmez)
                    $r->slug = $slug . '-x' . $r->id;
                    $r->save();
                    $r->delete();
                    $deduped++;
                }

                // Görsel dedup: yalnız ilk görseli tut (eski organikgiller ikincil görselini sil)
                $imgs = $keep->images()->orderBy('sort_order')->orderBy('id')->get();
                foreach ($imgs->skip(1) as $extra) {
                    $extra->delete();
                }
            }
        }

        $this->info($dry
            ? '[DRY-RUN] Yukarıdaki durum. Çalıştırmak için --dry-run olmadan tekrar edin.'
            : "Tamam: {$fixed} ürün aktif+kategori düzeltildi, {$deduped} duplika temizlendi.");

        return self::SUCCESS;
    }
}
