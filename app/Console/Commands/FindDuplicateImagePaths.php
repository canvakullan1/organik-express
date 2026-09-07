<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Aynı dosya yoluna (path) sahip, ama FARKLI ürünlere bağlı ProductImage
 * kayıtlarını bulur. "Görseli sildim ama hâlâ duruyor" şikayetinin en olası
 * sebebi: aynı görünen fotoğrafın aslında BAŞKA bir ürüne ait AYRI bir kaydı.
 *
 *   php artisan images:find-duplicates
 *   php artisan images:find-duplicates --path=organik-patates-1.jpg
 */
class FindDuplicateImagePaths extends Command
{
    protected $signature = 'images:find-duplicates {--path=}';

    protected $description = 'Aynı dosya yoluna sahip, farklı ürünlere bağlı görsel kayıtlarını listeler';

    public function handle(): int
    {
        if ($path = $this->option('path')) {
            $rows = ProductImage::withoutGlobalScopes()
                ->where('path', 'like', "%{$path}")
                ->with(['product' => fn ($q) => $q->withoutGlobalScopes()->withTrashed()])
                ->get();
            $this->line("path like %{$path}: " . $rows->count() . ' kayıt');
            foreach ($rows as $r) {
                $p = $r->product;
                $this->line("  image #{$r->id} product_id={$r->product_id} -> " . ($p ? "#{$p->id} {$p->name} (trashed=" . ($p->trashed() ? 'EVET' : 'hayır') . ')' : 'ÜRÜN YOK'));
            }

            return self::SUCCESS;
        }

        $dupes = DB::table('product_images')
            ->select('path', DB::raw('COUNT(DISTINCT product_id) as urun_sayisi'), DB::raw('COUNT(*) as kayit_sayisi'))
            ->groupBy('path')
            ->havingRaw('COUNT(DISTINCT product_id) > 1')
            ->orderByDesc('urun_sayisi')
            ->limit(30)
            ->get();

        $this->line('Birden fazla ürüne bağlı aynı dosya yolu: ' . $dupes->count() . ' (ilk 30 gösteriliyor)');
        foreach ($dupes as $d) {
            $this->line("  {$d->path}  -> {$d->urun_sayisi} farklı ürün, {$d->kayit_sayisi} kayıt");
        }

        $total = DB::table('product_images')
            ->select('path')
            ->groupBy('path')
            ->havingRaw('COUNT(DISTINCT product_id) > 1')
            ->get()
            ->count();
        $this->info("TOPLAM aynı-dosya-farklı-ürün sayısı: {$total}");

        return self::SUCCESS;
    }
}
