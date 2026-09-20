<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Mevcut ürün adlarını (yerelde DB'ye erişemediğimiz için) dışa aktarır —
 * harici site kazımalarında aynı ürünü tekrar eklememek için karşılaştırma listesi.
 *
 *   php artisan catalog:export-names
 */
class ExportProductNames extends Command
{
    protected $signature = 'catalog:export-names';

    protected $description = 'Ürün adı | slug | üretici listesini dökümante eder (dış kaynak kıyası için)';

    public function handle(): int
    {
        Product::withTrashed()->with('producer')->orderBy('name')
            ->each(function (Product $p) {
                $this->line($p->name . ' | ' . $p->slug . ' | ' . ($p->producer->name ?? '') . ' | ' . ($p->trashed() ? 'SILINMIS' : $p->status->value));
            });

        return self::SUCCESS;
    }
}
