<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Producer;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * organikgiller kataloğuna göre siteyi düzenler:
 *  - 6 üst (parent) kategori oluşturur, 17 kategoriyi altlarına yerleştirir (temiz header menüsü)
 *  - her kategoriye temsilci ürün görseli atar
 *  - öne çıkan / mevsim ürünlerini işaretler (anasayfa vitrinleri)
 *
 *   php artisan catalog:setup-site
 */
class SetupCatalogSite extends Command
{
    protected $signature = 'catalog:setup-site';

    protected $description = 'Siteyi organikgiller kataloğuna göre ayarlar (üst kategoriler, menü, görseller, vitrinler)';

    /** Üst kategori => [ad, alt kategori slugları] */
    private array $groups = [
        'meyve-sebze' => ['Meyve & Sebze', ['taze-meyve', 'taze-sebze']],
        'sut-kahvaltilik' => ['Süt & Kahvaltılık', ['sut-urunleri', 'yumurta', 'kahvaltilik-recel', 'zeytin-zeytinyagi-yag']],
        'et-tavuk' => ['Et & Tavuk', ['et-sarkuteri', 'hazir-yemek']],
        'bakkaliye' => ['Bakkaliye', ['bakliyat-makarna', 'baharat-aktar', 'sos-salca-sirke', 'firin-ekmek', 'kuruyemis-kurutulmus']],
        'icecek-atistirmalik' => ['İçecek & Atıştırmalık', ['icecek-cay', 'tatli-cikolata']],
        'dogal-yasam-temizlik' => ['Doğal Yaşam & Temizlik', []],
    ];

    /** Anasayfada öne çıkacak ürünler (slug). */
    private array $featured = [
        'organik-sizma-zeytinyagi-5-lt', 'organik-keci-basma-tulum-peyniri-425-gr', 'organik-tahin-300-gr',
        'organik-yumurta-30-lu', 'organik-dana-kiyma-500-gr', 'organik-cilek', 'organik-domates',
        'organik-kirmizi-mercimek-750-gr', 'organik-siyah-zeytin-salamura-700-gr', 'organik-bogurtlen-receli-280-gr',
        'organik-kabuklu-ceviz', 'organik-pastorize-sut-1-lt',
    ];

    public function handle(): int
    {
        // 1) Üst kategorileri oluştur ve altları bağla
        $order = 1;
        foreach ($this->groups as $slug => [$name, $childSlugs]) {
            $parent = Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'parent_id' => null,
                    'is_active' => true,
                    'show_in_menu' => true,
                    'sort_order' => $order,
                    'meta_title' => $name . ' | Organik Express',
                    'meta_description' => $name . ' kategorisindeki organik ürünleri keşfedin; katkısız, doğal ve taze kapınızda.',
                ],
            );

            $childOrder = 1;
            foreach ($childSlugs as $cs) {
                $child = Category::where('slug', $cs)->first();
                if ($child) {
                    $child->parent_id = $parent->id;
                    $child->is_active = true;
                    $child->show_in_menu = true;
                    $child->sort_order = $childOrder++;
                    $child->save();
                }
            }
            $order++;
        }

        // 1b) Bilinen 23 kategori (6 üst + 17 alt) dışındaki eski/demo kategorileri pasifleştir
        $known = array_keys($this->groups);
        foreach ($this->groups as [$name, $childSlugs]) {
            $known = array_merge($known, $childSlugs);
        }
        $catOff = Category::whereNotIn('slug', $known)
            ->where(fn ($q) => $q->where('is_active', true)->orWhere('show_in_menu', true))
            ->update(['is_active' => false, 'show_in_menu' => false]);

        // 2) Kategori görselleri — temsilci ürün görselinden
        $imgSet = 0;
        foreach (Category::all() as $cat) {
            $ids = $cat->children()->pluck('id')->push($cat->id);
            $rep = Product::whereIn('category_id', $ids)
                ->whereHas('images')->with('images')->orderBy('id')->first();
            $path = $rep?->images->first()?->path;
            if ($path && $cat->image !== $path) {
                $cat->image = $path;
                $cat->save();
                $imgSet++;
            }
        }

        // 3) Öne çıkan + mevsim ürünleri
        Product::query()->update(['is_featured' => false]);
        $fCount = Product::whereIn('slug', $this->featured)->update(['is_featured' => true]);

        // Mevsim = taze meyve + taze sebze
        Product::query()->update(['is_seasonal' => false]);
        $produceIds = Category::whereIn('slug', ['taze-meyve', 'taze-sebze'])->pluck('id');
        $sCount = Product::whereIn('category_id', $produceIds)->update(['is_seasonal' => true]);

        // 4) Hero banner linklerini yeni kataloğa göre düzelt (görselleri korunur)
        $bannerFix = 0;
        foreach (Banner::where('position', 'hero')->get() as $b) {
            $t = mb_strtolower($b->title . ' ' . $b->subtitle, 'UTF-8');
            $link = null;
            if (str_contains($t, 'sebze') || str_contains($t, 'meyve')) {
                $link = '/kategori/meyve-sebze';
            } elseif (str_contains($t, 'zeytinyağ') || str_contains($t, 'zeytin yağ')) {
                $link = '/kategori/zeytin-zeytinyagi-yag';
            } elseif (str_contains($t, 'kahvaltı') || str_contains($t, 'peynir') || str_contains($t, 'süt')) {
                $link = '/kategori/sut-kahvaltilik';
            } elseif (str_contains($t, 'et') || str_contains($t, 'tavuk')) {
                $link = '/kategori/et-tavuk';
            }
            $changed = false;
            if ($link && $b->link !== $link) {
                $b->link = $link;
                $changed = true;
            }
            if (blank($b->button_text)) {
                $b->button_text = 'Hemen Keşfet';
                $changed = true;
            }
            // Satmadığımız ürünleri (bal) içeren alt başlığı düzelt
            if ($b->subtitle && str_contains(mb_strtolower($b->subtitle, 'UTF-8'), 'bal')) {
                $b->subtitle = 'Köy peyniri, kefir ve tereyağı';
                $changed = true;
            }
            if ($changed) {
                $b->save();
                $bannerFix++;
            }
        }

        // 5) Katalogla eşleşmeyen demo kutuları gizle. Üreticiler artık gerçek (Üreticilerimiz sayfası) — onlara DOKUNMA.
        $prodOff = 0;
        $bundleOff = Bundle::where('is_active', true)->update(['is_active' => false]);

        // 6) Header menüsünü yeni kategorilere göre yeniden kur (eski/olmayan kategori linklerini temizler)
        MenuItem::where('location', 'header')->delete();
        $menuOrder = 1;
        foreach ($this->groups as $slug => [$name, $childSlugs]) {
            $parentCat = Category::where('slug', $slug)->first();
            if (! $parentCat) {
                continue;
            }
            $menuParent = MenuItem::create([
                'location' => 'header', 'parent_id' => null,
                'label' => $name, 'type' => 'category', 'reference_id' => $parentCat->id,
                'sort_order' => $menuOrder++, 'is_active' => true, 'target_blank' => false,
            ]);
            $childOrder = 1;
            foreach ($childSlugs as $cs) {
                $childCat = Category::where('slug', $cs)->first();
                if ($childCat) {
                    MenuItem::create([
                        'location' => 'header', 'parent_id' => $menuParent->id,
                        'label' => $childCat->name, 'type' => 'category', 'reference_id' => $childCat->id,
                        'sort_order' => $childOrder++, 'is_active' => true, 'target_blank' => false,
                    ]);
                }
            }
        }
        $menuCount = MenuItem::where('location', 'header')->count();

        $this->info('Üst kategori: ' . count($this->groups) . ' hazır.');
        $this->info("Pasifleştirilen eski/demo kategori: {$catOff}");
        $this->info("Kategori görseli atanan: {$imgSet}");
        $this->info("Öne çıkan ürün: {$fCount} | Mevsim ürünü: {$sCount}");
        $this->info("Düzenlenen hero banner: {$bannerFix}");
        $this->info("Gizlenen demo üretici: {$prodOff} | demo kutu: {$bundleOff}");
        $this->info("Header menü öğesi (yeniden kuruldu): {$menuCount}");

        return self::SUCCESS;
    }
}
