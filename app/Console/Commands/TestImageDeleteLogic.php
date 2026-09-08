<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;

/**
 * ProductImageController::destroy() içindeki KARŞILAŞTIRMAYI birebir taklit
 * eder ve her iki değerin gerçek TİPİNİ (int/string) gösterir. "Bu görsel bu
 * ürüne ait değil" mesajı veri doğruyken bile çıkıyorsa, sebep muhtemelen
 * tip uyuşmazlığıdır (=== / !== katı karşılaştırma).
 *
 *   php artisan images:test-delete-logic 256 460
 */
class TestImageDeleteLogic extends Command
{
    protected $signature = 'images:test-delete-logic {product} {image}';

    protected $description = 'destroy() karşılaştırmasını birebir simüle eder, tip farkını gösterir';

    public function handle(): int
    {
        $productId = $this->argument('product');
        $imageId = $this->argument('image');

        $this->line('Girdi (route parametresi gibi, HAM): product=' . var_export($productId, true) . ' image=' . var_export($imageId, true));

        // Controller'daki gibi int cast (route: int $product, int $image)
        $product = (int) $productId;
        $image = (int) $imageId;
        $this->line('int cast sonrası: product=' . var_export($product, true) . ' image=' . var_export($image, true));

        $p = Product::withoutGlobalScopes()->find($product);
        if (! $p) {
            $this->error('Ürün bulunamadı.');

            return self::FAILURE;
        }

        $img = ProductImage::find($image);
        if (! $img) {
            $this->error('Görsel bulunamadı.');

            return self::FAILURE;
        }

        $this->line('$p->id         = ' . var_export($p->id, true) . '  (tip: ' . gettype($p->id) . ')');
        $this->line('$img->product_id = ' . var_export($img->product_id, true) . '  (tip: ' . gettype($img->product_id) . ')');
        $this->line('$img->id       = ' . var_export($img->id, true) . '  (tip: ' . gettype($img->id) . ')');

        $strictNotEqual = $img->product_id !== $p->id;
        $looseNotEqual = $img->product_id != $p->id;

        $this->line('');
        $this->line('KATI (!==) sonucu : ' . var_export($strictNotEqual, true) . ($strictNotEqual ? '  <<< CONTROLLER BURADA "AIT DEĞİL" DER' : '  (eşit, sorun yok)'));
        $this->line('GEVŞEK (!=) sonucu: ' . var_export($looseNotEqual, true));

        return self::SUCCESS;
    }
}
