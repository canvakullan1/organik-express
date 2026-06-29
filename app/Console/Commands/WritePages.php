<?php

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;

/**
 * Kurumsal/yardım/yasal sayfaların içeriğini profesyonel metinlerle günceller.
 * Veri: database/data/pages.json (slug ile eşleşir).
 *
 *   php artisan pages:write
 */
class WritePages extends Command
{
    protected $signature = 'pages:write';

    protected $description = 'Sayfa içeriklerini (Hakkımızda, sözleşmeler, politikalar, SSS) profesyonel metinlerle yazar';

    public function handle(): int
    {
        $path = database_path('data/pages.json');
        if (! is_file($path)) {
            $this->error('database/data/pages.json bulunamadı.');

            return self::FAILURE;
        }

        $pages = json_decode(file_get_contents($path), true);
        if (! is_array($pages)) {
            $this->error('pages.json geçersiz.');

            return self::FAILURE;
        }

        $updated = $created = 0;
        foreach ($pages as $p) {
            $existed = Page::where('slug', $p['slug'])->exists();
            Page::updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'title' => $p['title'],
                    'excerpt' => $p['excerpt'] ?? null,
                    'content' => $p['content'],
                    'meta_title' => $p['meta_title'] ?? null,
                    'meta_description' => $p['meta_description'] ?? null,
                    'is_published' => true,
                    'show_in_footer' => true,
                    'footer_group' => $p['footer_group'] ?? 'kurumsal',
                    'sort_order' => $p['sort_order'] ?? 0,
                ],
            );
            $existed ? $updated++ : $created++;
        }

        $this->info("Sayfa: {$updated} güncellendi, {$created} oluşturuldu.");

        return self::SUCCESS;
    }
}
