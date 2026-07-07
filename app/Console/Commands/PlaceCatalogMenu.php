<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Console\Command;

/**
 * Fırın & Ekmek ve Simit & Poğaça kategorilerini AYRI ÜST-SEVİYE kategori yapar
 * ve header mega menüsüne üst-seviye öğe olarak ekler (Bakkaliye'nin hemen ardına).
 *
 *  - Kategori ağacı: parent_id = null (kök), aktif, menüde. (mobil menü + breadcrumb)
 *  - Header menü: Bakkaliye altındaki alt-öğe silinir, üst-seviye MenuItem oluşturulur.
 *  - Sıralama: hem kök kategoriler hem üst-seviye MenuItem'lar Bakkaliye'den hemen
 *    sonraya alınır; diğer öğelerin göreli sırası korunur. Tam idempotenttir.
 *
 * Tam `catalog:setup-site` çalıştırmadan (öne çıkan/mevsim ürünleri SIFIRLAMADAN)
 * sadece menü yerleşimini onarır.
 *
 *   php artisan catalog:place-menu
 */
class PlaceCatalogMenu extends Command
{
    protected $signature = 'catalog:place-menu';

    protected $description = 'Fırın & Ekmek ve Simit & Poğaça\'yı ayrı üst-seviye kategori + header öğesi yapar';

    /** Üst seviyeye çıkarılacak kategoriler: slug => görünen ad. Sıra = header sırası. */
    private array $topLevel = [
        'firin-ekmek' => 'Fırın & Ekmek',
        'simit-pogaca' => 'Simit & Poğaça',
    ];

    /** Bu üst-seviye kategoriler bu slug'ın hemen ardına dizilir. */
    private string $anchorSlug = 'bakkaliye';

    public function handle(): int
    {
        $moveCatIds = [];

        foreach ($this->topLevel as $slug => $label) {
            $cat = Category::where('slug', $slug)->first();
            if (! $cat) {
                $this->warn("Atlandı ({$slug}): kategori yok.");

                continue;
            }

            // 1) Kategoriyi köke çıkar (Bakkaliye'den ayır)
            $cat->update(['parent_id' => null, 'is_active' => true, 'show_in_menu' => true]);

            // 2) Header: Bakkaliye altındaki alt-öğeyi sil, üst-seviye öğeyi garanti et
            MenuItem::where('location', 'header')->where('type', 'category')
                ->where('reference_id', $cat->id)->whereNotNull('parent_id')->delete();

            MenuItem::updateOrCreate(
                ['location' => 'header', 'type' => 'category', 'reference_id' => $cat->id, 'parent_id' => null],
                ['label' => $label, 'is_active' => true],
            );

            $moveCatIds[] = $cat->id;
            $this->info("Üst seviyeye alındı: {$slug} → {$label}");
        }

        // 3) Sıralama (idempotent): anchor'dan hemen sonra
        $this->reorderRoots(array_keys($this->topLevel));
        $this->reorderHeaderTop($moveCatIds);

        $this->info('Menü sıralaması güncellendi.');

        return self::SUCCESS;
    }

    /** Kök kategorileri sırala: taşınanlar anchor kökünden hemen sonra (mobil menü/kısayollar). */
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

        $isMove = fn (MenuItem $m) => $m->type === 'category' && in_array($m->reference_id, $moveCatIds, true);
        $isAnchor = fn (MenuItem $m) => $m->type === 'category' && (int) $m->reference_id === $anchorCat->id;

        // Taşınacak öğeleri $moveCatIds sırasına göre diz
        $moves = $tops->filter($isMove)
            ->sortBy(fn (MenuItem $m) => array_search($m->reference_id, $moveCatIds))
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
