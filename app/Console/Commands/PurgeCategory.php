<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Bir kategoriyi, tüm alt kategorilerini ve içindeki ürünleri (varyant +
 * görseller dahil) KALICI olarak siler. Menü öğeleri de temizlenir.
 *
 *   php artisan catalog:purge-category hediye-sepeti
 */
class PurgeCategory extends Command
{
    protected $signature = 'catalog:purge-category {slug}';

    protected $description = 'Kategoriyi, alt kategorilerini ve ürünlerini (varyant + görsel) kalıcı siler';

    public function handle(): int
    {
        $slug = trim($this->argument('slug'));
        $root = Category::where('slug', $slug)->first();
        if (! $root) {
            $this->error("Kategori bulunamadi: {$slug}");

            return self::FAILURE;
        }

        // Hedef + tüm alt kategoriler (çocuklar önce silinecek diye sonra reverse)
        $cats = collect([$root]);
        $this->collectChildren($root, $cats);
        $catIds = $cats->pluck('id')->all();

        $productCount = 0;
        $imageFiles = 0;

        $products = Product::withTrashed()->whereIn('category_id', $catIds)->get();
        foreach ($products as $p) {
            foreach ($p->images as $img) {
                if ($img->path) {
                    Storage::disk('public')->delete($img->path);            // servis edilen disk
                    $repoFile = storage_path('app/public/' . $img->path);   // deploy kaynagi
                    if (is_file($repoFile)) {
                        @unlink($repoFile);
                    }
                    $imageFiles++;
                }
            }
            $p->forceDelete(); // FK cascade -> varyant + gorsel satirlari da silinir
            $productCount++;
        }

        // Bu kategorilere işaret eden menü öğeleri
        $menuDeleted = MenuItem::where('type', 'category')->whereIn('reference_id', $catIds)->delete();

        // Kategori görsel dosyaları + kategorileri sil (çocuklar önce)
        $catDeleted = 0;
        foreach ($cats->reverse() as $c) {
            if (! empty($c->image)) {
                Storage::disk('public')->delete($c->image);
                $repoFile = storage_path('app/public/' . $c->image);
                if (is_file($repoFile)) {
                    @unlink($repoFile);
                }
            }
            $c->delete();
            $catDeleted++;
        }

        $this->info("Silindi -> kategori: {$catDeleted}, urun: {$productCount}, gorsel-dosya: {$imageFiles}, menu-ogesi: {$menuDeleted}");

        return self::SUCCESS;
    }

    private function collectChildren(Category $cat, \Illuminate\Support\Collection $bag): void
    {
        foreach (Category::where('parent_id', $cat->id)->get() as $child) {
            $bag->push($child);
            $this->collectChildren($child, $bag);
        }
    }
}
