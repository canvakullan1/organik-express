<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Ürünü olduğu hâlde pasif kalan kategorileri erişilebilir yapar.
 *
 * Pasif kategorideki ürünler sitede hiçbir listede görünmez ve /kategori/<slug>
 * 404 verir (ürün sayfası açılsa bile). Bu komut:
 *   - Eski taksonomiden kalan `taze-meyve-sebze-*` kategorilerindeki ürünleri
 *     adına göre taze-meyve / taze-sebze'ye taşır.
 *   - Kalan, ürünü olan pasif kategorileri üst zinciriyle birlikte aktifler.
 *
 *   php artisan catalog:fix-category-visibility --dry-run
 */
class FixCategoryVisibility extends Command
{
    protected $signature = 'catalog:fix-category-visibility {--dry-run}';

    protected $description = 'Ürünü olup pasif kalan kategorileri erişilebilir yapar';

    /** Meyve sayılan anahtar kelimeler (kalanlar sebze). */
    private array $fruit = ['elma', 'armut', 'kiraz', 'vişne', 'erik', 'şeftali', 'kayısı', 'incir',
        'üzüm', 'karpuz', 'kavun', 'çilek', 'muz', 'portakal', 'mandalina', 'limon', 'greyfurt',
        'nar', 'ayva', 'kivi', 'avokado', 'böğürtlen', 'ahududu', 'yaban mersini', 'dut', 'nektarin'];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        // 1) Eski taksonomi artığı kategorilerdeki ürünleri taşı
        $legacy = Category::where('slug', 'like', 'taze-meyve-sebze-%')->pluck('id', 'slug');
        $moved = 0;
        foreach ($legacy as $slug => $id) {
            foreach (Product::where('category_id', $id)->get() as $p) {
                $name = mb_strtolower($p->name, 'UTF-8');
                $isFruit = false;
                foreach ($this->fruit as $f) {
                    if (str_contains($name, $f)) {
                        $isFruit = true;
                        break;
                    }
                }
                $target = Category::where('slug', $isFruit ? 'taze-meyve' : 'taze-sebze')->first();
                if (! $target) {
                    continue;
                }
                $this->line(($dry ? '[DRY] ' : '') . "taşı: {$p->name}  {$slug} -> {$target->slug}");
                if (! $dry) {
                    $p->category_id = $target->id;
                    $p->save();
                }
                $moved++;
            }
        }

        // 2) Ürünü olan pasif kategorileri (üst zinciriyle) aktifle
        $activated = 0;
        foreach (Category::withCount('products')->get() as $c) {
            if ($c->products_count < 1 || ($c->is_active && $c->show_in_menu)) {
                continue;
            }
            if (str_starts_with($c->slug, 'taze-meyve-sebze-')) {
                continue; // taşındı, aktifleme
            }
            $node = $c;
            while ($node) {
                if (! $node->is_active || ! $node->show_in_menu) {
                    $this->line(($dry ? '[DRY] ' : '') . "aktifle: {$node->slug}");
                    if (! $dry) {
                        $node->is_active = true;
                        $node->show_in_menu = true;
                        $node->save();
                    }
                    $activated++;
                }
                $node = $node->parent_id ? Category::find($node->parent_id) : null;
            }
        }

        $this->info(($dry ? '[DRY-RUN] ' : '') . "Taşınan ürün: {$moved} | Aktifleşen kategori: {$activated}");

        return self::SUCCESS;
    }
}
