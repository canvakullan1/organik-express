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
                    'meta_title' => $p['meta_title'] ?? $keep->meta_title,
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

                // VARYANT SIFIRLA + 1/2/3 birim varyasyonları oluştur.
                // Sabit toplam fiyatlı ayrı varyantlar (1 kg, 2 kg, 3 kg / demet / adet).
                // Toplu indirim: 2 birim %5, 3 birim %10; 5 TL'ye yuvarlanır. 1 birim = taban.
                $keep->variants()->delete();
                $unit = $p['unit'] ?? 'adet';
                $base = (float) ($p['price'] ?? 0);
                foreach ([1, 2, 3] as $amount) {
                    if ($amount === 1) {
                        $price = $base;
                    } else {
                        $mult = $amount === 2 ? 1.90 : 2.70; // 2×(0.95), 3×(0.90)
                        $price = round(($base * $mult) / 5) * 5;
                    }
                    $keep->variants()->create([
                        'name' => $amount . ' ' . $unit,   // "1 kg" / "2 kg" / "3 kg"
                        'unit' => $unit,
                        'unit_amount' => $amount,
                        'price' => $price,
                        'stock' => 100,
                        'track_stock' => false,
                        'is_weight_based' => false,
                        'is_default' => $amount === 1,
                        'is_active' => true,
                    ]);
                }
            }
        }

        // TEMİZLİK: taze-meyve/taze-sebze'de JSON'da OLMAYAN slug'ları KALICI sil
        // (eski ekoorganik kalıntıları). Bu iki kategoride yalnız meyve-sebze ürünleri olur.
        $purged = 0;
        if (! $dry) {
            $jsonSlugs = collect($data['products'] ?? [])->pluck('slug')->filter()->all();
            $msCats = Category::whereIn('slug', ['taze-meyve', 'taze-sebze'])->pluck('id')->all();
            if ($msCats && $jsonSlugs) {
                foreach (Product::withTrashed()->whereIn('category_id', $msCats)->whereNotIn('slug', $jsonSlugs)->get() as $stale) {
                    $stale->images()->delete();
                    $stale->variants()->delete();
                    $stale->forceDelete();
                    $this->line("Silindi (eski): {$stale->slug}");
                    $purged++;
                }
            }
        }

        $this->info($dry
            ? '[DRY-RUN] Yukarıdaki durum. Çalıştırmak için --dry-run olmadan tekrar edin.'
            : "Tamam: {$fixed} ürün düzeltildi, {$deduped} duplika, {$purged} eski ürün silindi.");

        return self::SUCCESS;
    }
}
