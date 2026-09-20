<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Bir kategorinin tüm ürünlerini başka bir kategoriye taşır, kaynak kategoriyi
 * (ürünsüz kaldıktan sonra) menüden/siteden gizler — KALICI SİLMEZ, geri
 * alınabilir (is_active/show_in_menu = false).
 *
 *   php artisan catalog:merge-category et-sarkuteri et-tavuk --dry-run
 *   php artisan catalog:merge-category et-sarkuteri et-tavuk
 */
class MergeCategory extends Command
{
    protected $signature = 'catalog:merge-category {from} {to} {--dry-run}';

    protected $description = 'Bir kategorinin ürünlerini başka kategoriye taşır, kaynağı gizler';

    public function handle(): int
    {
        $fromSlug = $this->argument('from');
        $toSlug = $this->argument('to');

        $from = Category::where('slug', $fromSlug)->first();
        $to = Category::where('slug', $toSlug)->first();

        if (! $from) {
            $this->error("Kaynak kategori yok: {$fromSlug}");

            return self::FAILURE;
        }
        if (! $to) {
            $this->error("Hedef kategori yok: {$toSlug}");

            return self::FAILURE;
        }

        $products = Product::withoutGlobalScopes()->where('category_id', $from->id)->get(['id', 'name', 'slug']);
        $dry = (bool) $this->option('dry-run');

        $this->line("Kaynak : #{$from->id} {$from->name} ({$from->slug})");
        $this->line("Hedef  : #{$to->id} {$to->name} ({$to->slug})");
        $this->line('Taşınacak ürün: ' . $products->count());

        foreach ($products as $p) {
            $this->line(($dry ? '[DRY] ' : '') . "  #{$p->id} {$p->name}");
        }

        if ($dry) {
            $this->info('[DRY-RUN] Kaydedilmedi.');

            return self::SUCCESS;
        }

        $moved = Product::withoutGlobalScopes()->where('category_id', $from->id)->update(['category_id' => $to->id]);

        // Kaynak kategori artık ürünsüz — siteden/menüden gizle (kalıcı silme yok, geri alınabilir).
        $from->is_active = false;
        $from->show_in_menu = false;
        $from->save();

        $this->info("{$moved} ürün taşındı. '{$from->slug}' kategorisi gizlendi (silinmedi).");

        return self::SUCCESS;
    }
}
