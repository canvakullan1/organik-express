<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * "Pancar Kvası/Kvass" ürünleri (fermente içecek) Nisa taramasında "pancar"
 * kelimesinden dolayı yanlışlıkla taze-sebze'ye düşmüştü — icecek-cay'e taşır.
 *
 *   php artisan catalog:fix-kvass-category
 */
class FixKvassCategory extends Command
{
    protected $signature = 'catalog:fix-kvass-category';

    protected $description = 'Kvas/Kvass urunlerini taze-sebze\'den icecek-cay\'e tasir';

    public function handle(): int
    {
        $cat = Category::where('slug', 'icecek-cay')->firstOrFail();
        $moved = Product::where('name', 'like', '%vas%')
            ->where('category_id', '!=', $cat->id)
            ->where(fn ($q) => $q->where('name', 'like', '%Kvas%')->orWhere('name', 'like', '%kvas%'))
            ->update(['category_id' => $cat->id]);

        $this->info("{$moved} ürün icecek-cay'e taşındı.");

        return self::SUCCESS;
    }
}
