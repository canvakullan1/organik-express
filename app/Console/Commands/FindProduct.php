<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Bir ürünü ada/slug'a göre bulur ve TÜM görsel kayıtlarını (path + fiziksel
 * varlık + dosyanın kendi slug'ıyla uyuşup uyuşmadığını) gösterir.
 *
 *   php artisan products:find "Organik Buğday Unu"
 */
class FindProduct extends Command
{
    protected $signature = 'products:find {query}';

    protected $description = 'Ada/slug\'a göre ürün arar, görsellerini detaylı gösterir';

    public function handle(): int
    {
        $q = trim((string) $this->argument('query'));

        $products = Product::withoutGlobalScopes()
            ->where('name', 'like', "%{$q}%")
            ->orWhere('slug', 'like', "%{$q}%")
            ->get();

        if ($products->isEmpty()) {
            $this->warn('Eşleşen ürün yok.');

            return self::SUCCESS;
        }

        foreach ($products as $p) {
            $this->line(str_repeat('=', 60));
            $this->line("#{$p->id} {$p->name} (slug={$p->slug}, status={$p->status->value}, trashed=" . ($p->trashed() ? 'EVET' : 'hayır') . ')');
            $imgs = $p->images()->withoutGlobalScopes()->orderBy('sort_order')->get();
            if ($imgs->isEmpty()) {
                $this->line('  (görsel yok)');

                continue;
            }
            foreach ($imgs as $img) {
                $existsDisk = Storage::disk('public')->exists($img->path);
                $existsLocal = is_file(storage_path('app/public/' . $img->path));
                $fileSlug = preg_replace('/-\d+\.\w+$/', '', basename($img->path));
                $matches = $fileSlug === $p->slug;
                $this->line("  image #{$img->id} path={$img->path}");
                $this->line('    disk_var=' . ($existsDisk ? 'EVET' : 'HAYIR')
                    . ' local_var=' . ($existsLocal ? 'EVET' : 'HAYIR')
                    . ' dosya_slug=' . $fileSlug
                    . ' urun_slug_ile_uyumlu=' . ($matches ? 'EVET' : 'HAYIR <<<'));
            }
        }

        return self::SUCCESS;
    }
}
