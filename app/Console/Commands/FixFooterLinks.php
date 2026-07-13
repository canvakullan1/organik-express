<?php

namespace App\Console\Commands;

use App\Models\MenuItem;
use Illuminate\Console\Command;

/**
 * Footer menüsündeki bozuk (resolved_url='#') route-linklerini onarır.
 *
 * "Sertifikalar" / "Üreticiler" birer ROUTE'tur (Page değil); menüde type=page +
 * olmayan sayfa referansıyla tanımlanınca link "#" olur (çalışmaz). Bunları
 * type=custom + doğru URL'ye çevirir. İdempotent.
 *
 *   php artisan catalog:fix-footer-links
 */
class FixFooterLinks extends Command
{
    protected $signature = 'catalog:fix-footer-links';

    protected $description = 'Footer menüsündeki bozuk Sertifikalar/Üreticiler linklerini route URL\'sine çevirir';

    /** Etiket => hedef URL (route). */
    private array $map = [
        'Sertifikalar' => '/sertifikalar',
        'Üreticiler' => '/ureticiler',
    ];

    public function handle(): int
    {
        $fixed = 0;

        foreach (MenuItem::where('location', 'footer')->get() as $m) {
            if (isset($this->map[$m->label]) && $m->resolved_url === '#') {
                $m->update([
                    'type' => 'custom',
                    'reference_id' => null,
                    'url' => $this->map[$m->label],
                ]);
                $this->info("Onarıldı: {$m->label} → {$this->map[$m->label]}");
                $fixed++;
            }
        }

        $this->info($fixed ? "Tamam: {$fixed} link onarıldı." : 'Bozuk footer linki bulunamadı.');

        return self::SUCCESS;
    }
}
