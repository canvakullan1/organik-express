<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Console\Command;

/**
 * Sonradan eklenen kategorileri mevcut üst gruplara TEMİZ şekilde yerleştirir:
 *  - Kategori ağacı: child.parent_id = parent (mobil menü + breadcrumb düzelir)
 *  - Header mega menü: parent'ın header MenuItem'ı altına child MenuItem eklenir
 *
 * Tam `catalog:setup-site` çalıştırmadan (öne çıkan/mevsim ürünleri SIFIRLAMADAN)
 * sadece menü yerleşimini onarır. Idempotenttir; tekrar çalıştırmak zarar vermez.
 *
 *   php artisan catalog:place-menu
 */
class PlaceCatalogMenu extends Command
{
    protected $signature = 'catalog:place-menu';

    protected $description = 'Sonradan eklenen kategorileri üst gruplara yerleştirir (kategori ağacı + header menü)';

    /** child-slug => [parent-slug, kendisinden sonra sıralanacağı kardeş-slug|null] */
    private array $placements = [
        'simit-pogaca' => ['bakkaliye', 'firin-ekmek'],
    ];

    public function handle(): int
    {
        foreach ($this->placements as $childSlug => [$parentSlug, $afterSlug]) {
            $child = Category::where('slug', $childSlug)->first();
            $parent = Category::where('slug', $parentSlug)->first();

            if (! $child || ! $parent) {
                $this->warn("Atlandı ({$childSlug}): kategori veya üst kategori yok.");

                continue;
            }

            $this->placeCategory($child, $parent, $afterSlug);
            $this->placeMenuItem($child, $parent, $afterSlug);
            $this->info("Yerleştirildi: {$childSlug} → {$parentSlug}");
        }

        return self::SUCCESS;
    }

    /** Kategori ağacında child'ı parent altına, afterSlug kardeşinden hemen sonraya al. */
    private function placeCategory(Category $child, Category $parent, ?string $afterSlug): void
    {
        $order = $this->slotAfter(
            Category::where('parent_id', $parent->id),
            'slug',
            $afterSlug,
            $child->slug,
        );

        $child->parent_id = $parent->id;
        $child->is_active = true;
        $child->show_in_menu = true;
        $child->sort_order = $order;
        $child->save();
    }

    /** Header mega menüde parent'ın MenuItem'ı altına child için MenuItem oluştur/güncelle. */
    private function placeMenuItem(Category $child, Category $parent, ?string $afterSlug): void
    {
        // Header'da özel menü tanımlı değilse (fallback kategori menüsü) yapacak bir şey yok.
        $parentItem = MenuItem::where('location', 'header')
            ->where('type', 'category')
            ->where('reference_id', $parent->id)
            ->whereNull('parent_id')
            ->first();

        if (! $parentItem) {
            return; // header menüsü kategori-fallback modunda; MenuItem gerekmez
        }

        // afterSlug kardeşinin MenuItem'ından sonraya sırala
        $afterRefId = $afterSlug ? Category::where('slug', $afterSlug)->value('id') : null;
        $order = $this->slotAfterMenu($parentItem->id, $afterRefId);

        MenuItem::updateOrCreate(
            ['location' => 'header', 'parent_id' => $parentItem->id, 'type' => 'category', 'reference_id' => $child->id],
            ['label' => $child->name, 'sort_order' => $order, 'is_active' => true],
        );
    }

    /**
     * Bir grupta afterKey satırından hemen sonraki sort_order'ı döndürür ve
     * çakışan sonraki kardeşleri bir kaydırır. afterKey yoksa sona ekler.
     */
    private function slotAfter($query, string $keyColumn, ?string $afterKey, string $selfKey): int
    {
        $siblings = (clone $query)->where($keyColumn, '!=', $selfKey)->get();

        if ($afterKey) {
            $anchor = $siblings->firstWhere($keyColumn, $afterKey);
            if ($anchor) {
                $newOrder = ($anchor->sort_order ?? 0) + 1;
                (clone $query)->where($keyColumn, '!=', $selfKey)
                    ->where('sort_order', '>=', $newOrder)
                    ->increment('sort_order');

                return $newOrder;
            }
        }

        return (int) ((clone $query)->max('sort_order') ?? 0) + 1;
    }

    /** MenuItem grubunda afterRefId'den sonraki sıra; yoksa sona. */
    private function slotAfterMenu(int $parentItemId, ?int $afterRefId): int
    {
        $q = MenuItem::where('parent_id', $parentItemId);

        if ($afterRefId) {
            $anchor = (clone $q)->where('type', 'category')->where('reference_id', $afterRefId)->first();
            if ($anchor) {
                $newOrder = ($anchor->sort_order ?? 0) + 1;
                (clone $q)->where('sort_order', '>=', $newOrder)->increment('sort_order');

                return $newOrder;
            }
        }

        return (int) ((clone $q)->max('sort_order') ?? 0) + 1;
    }
}
