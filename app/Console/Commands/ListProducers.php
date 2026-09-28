<?php

namespace App\Console\Commands;

use App\Models\Producer;
use Illuminate\Console\Command;

/**
 * Tüm üretici adlarını listeler — harici kazımada zaten sahip olduğumuz bir
 * markayı (ör. Beyorganik, Essen) başka bir siteden ikinci kez çekmemek için.
 *
 *   php artisan catalog:list-producers
 */
class ListProducers extends Command
{
    protected $signature = 'catalog:list-producers';

    protected $description = 'Tüm üretici adlarını satır satır listeler';

    public function handle(): int
    {
        Producer::withCount('products')->orderBy('name')->each(function (Producer $p) {
            $this->line("{$p->name} | urun:{$p->products_count}");
        });

        return self::SUCCESS;
    }
}
