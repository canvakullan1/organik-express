<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;

/**
 * Bir görsel dosya adının hangi ürüne (ve durumuna) ait olduğunu bulur.
 * "Bu dosya neden hâlâ kullanımda görünüyor?" sorusunu teşhis etmek için.
 *
 *   php artisan images:find-owner "raya-organik-yumurta-8-li-m-boy-1.jpg"
 */
class FindImageOwner extends Command
{
    protected $signature = 'images:find-owner {filename}';

    protected $description = 'Bir görsel dosyasının hangi ürüne ait olduğunu gösterir';

    public function handle(): int
    {
        $filename = trim((string) $this->argument('filename'));
        $rows = ProductImage::withoutGlobalScopes()
            ->where('path', 'like', "%{$filename}")
            ->with(['product' => fn ($q) => $q->withoutGlobalScopes()->withTrashed()])
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('Bu dosya adına ait ProductImage kaydı yok.');

            return self::SUCCESS;
        }

        foreach ($rows as $r) {
            $p = $r->product;
            $status = $p ? ($p->trashed() ? 'SİLİNMİŞ (soft)' : (string) ($p->status?->value ?? $p->status)) : 'ÜRÜN YOK (yetim kayıt)';
            $this->line("path={$r->path}");
            $this->line('  product_id=' . ($p->id ?? '—') . ' slug=' . ($p->slug ?? '—') . ' name=' . ($p->name ?? '—'));
            $this->line("  durum: {$status}");
        }

        return self::SUCCESS;
    }
}
