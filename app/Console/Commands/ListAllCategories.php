<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;

/**
 * TÜM kategorileri (ürün sayısı 0 olanlar dahil) listeler — catalog:stats
 * sadece ürünü olanları gösteriyor, yeni bir kaynak için kategori eşleme
 * tablosu hazırlarken canlıdaki TAM taksonomiyi görmek gerekiyor.
 *
 *   php artisan catalog:list-categories
 */
class ListAllCategories extends Command
{
    protected $signature = 'catalog:list-categories';

    protected $description = 'Tüm kategorileri (boş olanlar dahil) slug/ad/üst ile listeler';

    public function handle(): int
    {
        Category::withCount('products')->with('parent')->orderBy('parent_id')->orderBy('sort_order')
            ->each(function (Category $c) {
                $this->line(sprintf(
                    '%-28s | %-30s | ust:%-20s | urun:%-4d | aktif:%s menu:%s',
                    $c->slug, $c->name, $c->parent?->slug ?? '-', $c->products_count,
                    $c->is_active ? 'E' : 'H', $c->show_in_menu ? 'E' : 'H'
                ));
            });

        return self::SUCCESS;
    }
}
