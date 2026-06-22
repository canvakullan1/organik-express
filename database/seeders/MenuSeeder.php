<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Mevcut (varsayılan) header ve footer menülerini MenuItem kayıtlarına aktarır,
 * böylece panelden düzenlenebilir hale gelirler. Idempotent: ilgili konumda
 * kayıt varsa o konumu atlar (kullanıcı özelleştirmesini ezmez).
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHeader();
        $this->seedFooter();
    }

    private function seedHeader(): void
    {
        if (MenuItem::where('location', 'header')->exists()) {
            return; // zaten var → dokunma
        }

        $sort = 0;
        $roots = Category::active()->roots()->where('show_in_menu', true)
            ->with(['children' => fn ($q) => $q->active()->where('show_in_menu', true)->orderBy('sort_order')])
            ->orderBy('sort_order')->get();

        foreach ($roots as $cat) {
            $parent = MenuItem::create([
                'location' => 'header',
                'label' => $cat->name,
                'type' => 'category',
                'reference_id' => $cat->id,
                'sort_order' => $sort++,
                'is_active' => true,
            ]);

            $childSort = 0;
            foreach ($cat->children as $child) {
                MenuItem::create([
                    'location' => 'header',
                    'parent_id' => $parent->id,
                    'label' => $child->name,
                    'type' => 'category',
                    'reference_id' => $child->id,
                    'sort_order' => $childSort++,
                    'is_active' => true,
                ]);
            }
        }

        // Blog bağlantısı
        MenuItem::create([
            'location' => 'header',
            'label' => 'Blog',
            'type' => 'custom',
            'url' => '/blog',
            'sort_order' => $sort++,
            'is_active' => true,
        ]);
    }

    private function seedFooter(): void
    {
        if (MenuItem::where('location', 'footer')->exists()) {
            return;
        }

        $sort = 0;
        // Footer sütunları = parent öğeler; altındaki linkler = çocuk öğeler.
        foreach (Page::FOOTER_GROUPS as $groupKey => $groupLabel) {
            $pages = Page::published()->where('show_in_footer', true)
                ->where('footer_group', $groupKey)->orderBy('sort_order')->get();

            if ($pages->isEmpty()) {
                continue;
            }

            $column = MenuItem::create([
                'location' => 'footer',
                'label' => $groupLabel,
                'type' => 'custom',
                'url' => null,
                'sort_order' => $sort++,
                'is_active' => true,
            ]);

            $childSort = 0;
            foreach ($pages as $page) {
                MenuItem::create([
                    'location' => 'footer',
                    'parent_id' => $column->id,
                    'label' => $page->title,
                    'type' => 'page',
                    'reference_id' => $page->id,
                    'sort_order' => $childSort++,
                    'is_active' => true,
                ]);
            }
        }
    }
}
