<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Fırın & Ekmek ve Simit & Poğaça yerleşimi:
 *  - İkisi de KÖK kategori kalır (ana sayfa "Kategoriler" bölümü kökleri gösterir).
 *  - Header mega menüsü (MenuItem tabanlı): Fırın & Ekmek üst-seviye; Simit & Poğaça
 *    onun ALT menüsünde (Fırın & Ekmek MenuItem'ının child'ı). Böylece masaüstü
 *    menüde Simit & Poğaça, Fırın & Ekmek altında; ana sayfada ise ikisi de kart olarak.
 *  - Kategori görselleri yoksa temsilci ürün görselinden atanır.
 *  - Fırın & Ekmek üst-seviyede Bakkaliye'nin hemen ardına dizilir. Tam idempotent.
 *
 * Tam `catalog:setup-site` çalıştırmadan (öne çıkan/mevsim ürünleri SIFIRLAMADAN) çalışır.
 *
 *   php artisan catalog:place-menu
 */
class PlaceCatalogMenu extends Command
{
    protected $signature = 'catalog:place-menu';

    protected $description = 'Fırın & Ekmek üst-seviye; Simit & Poğaça onun header alt menüsünde + kategori görselleri';

    /** Fırın & Ekmek üst-seviyede bu slug'ın hemen ardına dizilir. */
    private string $anchorSlug = 'bakkaliye';

    public function handle(): int
    {
        $firin = Category::where('slug', 'firin-ekmek')->first();
        $simit = Category::where('slug', 'simit-pogaca')->first();

        if (! $firin || ! $simit) {
            $this->warn('firin-ekmek veya simit-pogaca kategorisi bulunamadı.');

            return self::FAILURE;
        }

        // 1) İkisi de KÖK kategori kalsın (ana sayfa kategoriler bölümü kökleri gösterir)
        $firin->update(['parent_id' => null, 'is_active' => true, 'show_in_menu' => true]);
        $simit->update(['parent_id' => null, 'is_active' => true, 'show_in_menu' => true]);

        // 2) Header: Fırın & Ekmek üst-seviye MenuItem (varsa nested kopyayı sil)
        MenuItem::where('location', 'header')->where('type', 'category')
            ->where('reference_id', $firin->id)->whereNotNull('parent_id')->delete();

        $firinItem = MenuItem::updateOrCreate(
            ['location' => 'header', 'type' => 'category', 'reference_id' => $firin->id, 'parent_id' => null],
            ['label' => 'Fırın & Ekmek', 'is_active' => true],
        );

        // 3) Header: Simit & Poğaça → Fırın & Ekmek MenuItem'ının ALTINA (üst-seviye kopyayı sil)
        MenuItem::where('location', 'header')->where('type', 'category')
            ->where('reference_id', $simit->id)->whereNull('parent_id')->delete();

        MenuItem::updateOrCreate(
            ['location' => 'header', 'type' => 'category', 'reference_id' => $simit->id, 'parent_id' => $firinItem->id],
            ['label' => 'Simit & Poğaça', 'is_active' => true, 'sort_order' => 0],
        );

        // 4) Kategori görselleri (yoksa temsilci ürün görselinden)
        $this->ensureImage($firin);
        $this->ensureImage($simit);

        // 5) Sıralama (idempotent): Fırın & Ekmek üst-seviyede Bakkaliye'nin ardına.
        //    Kök kategori sırası (mobil menü + ana sayfa): firin, simit Bakkaliye'den sonra.
        $this->reorderRoots(['firin-ekmek', 'simit-pogaca']);
        $this->reorderHeaderTop([(int) $firin->id]);

        $this->info('Tamam: Fırın & Ekmek üst-seviye, Simit & Poğaça onun alt menüsünde. Görseller atandı.');

        return self::SUCCESS;
    }

    /** Kategori görseli yoksa (kendi veya alt kategori) temsilci ürün görselinden ata. */
    private function ensureImage(Category $cat): void
    {
        if ($cat->image) {
            return;
        }

        $ids = $cat->children()->pluck('id')->push($cat->id);
        $rep = Product::whereIn('category_id', $ids)->whereHas('images')
            ->with('images')->orderBy('id')->first();
        $path = $rep?->images->first()?->path;

        if ($path) {
            $cat->update(['image' => $path]);
            $this->info("Görsel atandı ({$cat->slug}): {$path}");
        } else {
            $this->warn("Görsel bulunamadı ({$cat->slug}): ürün görseli yok.");
        }
    }

    /** Kök kategorileri sırala: taşınanlar anchor kökünden hemen sonra (mobil menü + ana sayfa). */
    private function reorderRoots(array $moveSlugs): void
    {
        $slugs = Category::whereNull('parent_id')->orderBy('sort_order')->orderBy('id')->pluck('slug')->all();
        $ordered = $this->insertAfter($slugs, $this->anchorSlug, $moveSlugs);

        $order = 1;
        foreach ($ordered as $slug) {
            Category::where('slug', $slug)->update(['sort_order' => $order++]);
        }
    }

    /** Üst-seviye header MenuItem'ları sırala: taşınanlar Bakkaliye öğesinden hemen sonra. */
    private function reorderHeaderTop(array $moveCatIds): void
    {
        $anchorCat = Category::where('slug', $this->anchorSlug)->first();
        if (! $anchorCat) {
            return; // header kategori-fallback modunda olabilir
        }

        $tops = MenuItem::where('location', 'header')->whereNull('parent_id')
            ->orderBy('sort_order')->orderBy('id')->get();

        // reference_id bazı ortamlarda string döner → tip-güvenli karşılaştırma için int'e çevir
        $moveCatIds = array_map('intval', $moveCatIds);
        $anchorId = (int) $anchorCat->id;

        $isMove = fn (MenuItem $m) => $m->type === 'category' && in_array((int) $m->reference_id, $moveCatIds, true);
        $isAnchor = fn (MenuItem $m) => $m->type === 'category' && (int) $m->reference_id === $anchorId;

        // Taşınacak öğeleri $moveCatIds sırasına göre diz
        $moves = $tops->filter($isMove)
            ->sortBy(fn (MenuItem $m) => array_search((int) $m->reference_id, $moveCatIds, true))
            ->values();
        $rest = $tops->reject($isMove)->values();

        $ordered = collect();
        $anchorSeen = false;
        foreach ($rest as $m) {
            $ordered->push($m);
            if ($isAnchor($m)) {
                foreach ($moves as $mv) {
                    $ordered->push($mv);
                }
                $anchorSeen = true;
            }
        }
        if (! $anchorSeen) {
            foreach ($moves as $mv) {
                $ordered->push($mv);
            }
        }

        $order = 0;
        foreach ($ordered as $m) {
            $m->update(['sort_order' => $order++]);
        }
    }

    /**
     * $list'ten $moves'u çıkarır, $anchor'dan hemen sonraya ekler (sıra korunur).
     * $anchor yoksa sona ekler. İdempotent.
     */
    private function insertAfter(array $list, string $anchor, array $moves): array
    {
        $list = array_values(array_filter($list, fn ($s) => ! in_array($s, $moves, true)));

        $out = [];
        $inserted = false;
        foreach ($list as $s) {
            $out[] = $s;
            if ($s === $anchor) {
                foreach ($moves as $m) {
                    $out[] = $m;
                }
                $inserted = true;
            }
        }
        if (! $inserted) {
            foreach ($moves as $m) {
                $out[] = $m;
            }
        }

        return $out;
    }
}
