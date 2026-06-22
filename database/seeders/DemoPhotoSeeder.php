<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Post;
use App\Models\Producer;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo SVG yer tutucularını gerçek fotoğraflarla değiştirir (loremflickr, CC).
 * Yalnızca yerel demo içindir; canlıda müşterinin gerçek görselleri panelden yüklenir.
 * `lock` parametresi aynı anahtar için hep aynı fotoğrafı getirir (deterministik).
 */
class DemoPhotoSeeder extends Seeder
{
    public function run(): void
    {
        $this->banners();
        $this->categories();
        $this->products();
        $this->bundles();
        $this->producers();
        $this->posts();
    }

    /** Fotoğrafı indirip storage'a yaz; başarısızsa null döner (mevcut görsel korunur). */
    private function fetch(string $keywords, int $w, int $h, string $path, int $lock): ?string
    {
        try {
            $url = "https://loremflickr.com/{$w}/{$h}/{$keywords}?lock={$lock}";
            $response = Http::timeout(40)->get($url);

            if ($response->successful() && strlen($response->body()) > 5000) {
                Storage::disk('public')->put($path, $response->body());

                return $path;
            }
        } catch (\Throwable $e) {
            $this->command?->warn("  ! {$keywords} indirilemedi: " . $e->getMessage());
        }

        return null;
    }

    private function banners(): void
    {
        $map = [
            'demo-taze-sebze' => 'vegetables,market',
            'demo-zeytinyagi' => 'olive,oil',
            'demo-kahvaltilik' => 'breakfast,cheese',
        ];

        foreach (Banner::all() as $banner) {
            $key = pathinfo((string) $banner->image, PATHINFO_FILENAME);
            $keywords = $map[$key] ?? 'organic,vegetables';
            $path = $this->fetch($keywords, 1600, 700, "banners/photo-{$banner->id}.jpg", 100 + $banner->id);
            if ($path) {
                $banner->update(['image' => $path]);
                $this->command?->info("  banner #{$banner->id} ✓");
            }
        }
    }

    private function categories(): void
    {
        $map = [
            'taze-meyve' => 'vegetables,fruit',
            'kahvaltilik' => 'cheese,breakfast',
            'zeytin' => 'olives',
            'kuru-gida' => 'legumes,grain',
            'et' => 'fish,seafood',
            'temizlik' => 'soap,natural',
            'kisisel-bakim' => 'cosmetics,natural',
        ];

        foreach (Category::whereNull('parent_id')->get() as $cat) {
            $keywords = collect($map)->first(fn ($v, $k) => str_contains($cat->slug, $k)) ?? 'organic,food';
            $path = $this->fetch($keywords, 800, 800, "categories/photo-{$cat->id}.jpg", 200 + $cat->id);
            if ($path) {
                $cat->update(['image' => $path]);
                $this->command?->info("  kategori {$cat->slug} ✓");
            }
        }
    }

    private function products(): void
    {
        $map = [
            'domates' => 'tomatoes', 'salatalik' => 'cucumber', 'biber' => 'peppers',
            'maydanoz' => 'parsley,herbs', 'patates' => 'potatoes', 'elma' => 'apples',
            'limon' => 'lemons', 'zeytinyag' => 'olive,oil', 'zeytin' => 'olives',
            'bal' => 'honey', 'peynir' => 'cheese', 'yumurta' => 'eggs',
            'tereyag' => 'butter', 'un' => 'flour,bread', 'mercimek' => 'lentils',
            'nohut' => 'chickpeas', 'bulgur' => 'grain', 'ceviz' => 'walnuts',
            'badem' => 'almonds', 'tavuk' => 'chicken,food', 'balik' => 'fish',
            'sut' => 'milk', 'roka' => 'arugula,salad', 'tere' => 'greens',
            'zencefil' => 'ginger',
        ];

        foreach (Product::with('images')->get() as $product) {
            $slug = Str::slug($product->name);
            $keywords = collect($map)->first(fn ($v, $k) => str_contains($slug, $k)) ?? 'organic,food';
            $path = $this->fetch($keywords, 900, 900, "products/photo-{$product->id}.jpg", 300 + $product->id);
            if ($path) {
                // Tek kapak görseli: varsa ilkini güncelle, yoksa oluştur
                $first = $product->images->first();
                $first
                    ? $first->update(['path' => $path])
                    : $product->images()->create(['path' => $path, 'alt' => $product->name, 'sort_order' => 0]);
                $this->command?->info("  ürün {$product->name} ✓");
            }
        }
    }

    private function bundles(): void
    {
        $map = [
            'sebze' => 'vegetable,basket',
            'kahvalti' => 'breakfast,table',
            'detoks' => 'fruit,smoothie',
        ];

        foreach (Bundle::all() as $bundle) {
            $keywords = collect($map)->first(fn ($v, $k) => str_contains($bundle->slug, $k)) ?? 'vegetables,box';
            $path = $this->fetch($keywords, 1200, 900, "bundles/photo-{$bundle->id}.jpg", 400 + $bundle->id);
            if ($path) {
                $bundle->update(['image' => $path]);
                $this->command?->info("  kutu {$bundle->slug} ✓");
            }
        }
    }

    private function producers(): void
    {
        $map = [
            'zeytin' => 'olive,grove',
            'toros' => 'farm,greenhouse',
            'aricilik' => 'beekeeping,honey',
            'sut' => 'dairy,farm',
        ];

        foreach (Producer::all() as $producer) {
            $keywords = collect($map)->first(fn ($v, $k) => str_contains($producer->slug, $k)) ?? 'farm,field';
            $path = $this->fetch($keywords, 1200, 750, "producers/photo-{$producer->id}.jpg", 500 + $producer->id);
            if ($path) {
                $producer->update(['image' => $path]);
                $this->command?->info("  üretici {$producer->slug} ✓");
            }
        }
    }

    private function posts(): void
    {
        $map = [
            'enginar' => 'artichoke,food', 'mercimek' => 'lentil,soup',
            'peynir' => 'baked,vegetables', 'organik' => 'organic,vegetables',
            'mevsim' => 'seasonal,fruit', 'zeytin' => 'olive,harvest',
        ];

        foreach (Post::all() as $post) {
            $keywords = collect($map)->first(fn ($v, $k) => str_contains($post->slug, $k)) ?? 'healthy,food';
            $path = $this->fetch($keywords, 1200, 750, "blog/photo-{$post->id}.jpg", 600 + $post->id);
            if ($path) {
                $post->update(['cover_image' => $path]);
                $this->command?->info("  yazı {$post->slug} ✓");
            }
        }
    }
}
