<?php

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Satış analitiği panelini dolu göstermek için son 30 günün
 * gerçekçi funnel + atıf verisini üretir. (Yalnızca demo / test.)
 */
class AnalyticsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::with('variants')->get();
        if ($products->isEmpty()) {
            return;
        }

        // [channel, source, medium] ağırlıklı kaynak havuzu
        $sources = [
            ['organic', 'google', 'organic'],
            ['organic', 'google', 'organic'],
            ['paid', 'google', 'cpc'],
            ['social', 'instagram', 'social'],
            ['social', 'instagram', 'paid-social'],
            ['social', 'facebook', 'social'],
            ['direct', null, null],
            ['direct', null, null],
            ['referral', 'tazedirekt.com', 'referral'],
            ['email', 'newsletter', 'email'],
        ];

        $rows = [];
        for ($d = 29; $d >= 0; $d--) {
            $day = now()->subDays($d);
            $sessionCount = rand(8, 25); // günlük ziyaret

            for ($s = 0; $s < $sessionCount; $s++) {
                $sessionId = Str::random(40);
                [$channel, $source, $medium] = $sources[array_rand($sources)];
                $ts = $day->copy()->setTime(rand(8, 22), rand(0, 59), rand(0, 59));

                $attr = [
                    'session_id' => $sessionId,
                    'channel' => $channel,
                    'source' => $source,
                    'medium' => $medium,
                    'device' => ['mobile', 'mobile', 'desktop', 'tablet'][array_rand([0, 1, 2, 3])],
                ];

                // page_view (landing)
                $rows[] = $this->event('page_view', $attr, $ts);

                // %70 ürün görüntüler
                if (rand(1, 100) <= 70) {
                    $product = $products->random();
                    $variant = $product->variants->first();
                    $price = (float) ($variant->price ?? 0);
                    $rows[] = $this->event('product_view', $attr, $ts->copy()->addMinutes(1), $product->id, $variant?->id, null, $price);

                    // %40 sepete ekler
                    if (rand(1, 100) <= 40) {
                        $qty = rand(1, 3);
                        $rows[] = $this->event('add_to_cart', $attr, $ts->copy()->addMinutes(3), $product->id, $variant?->id, $qty, $price * $qty);

                        // %15 sepetten çıkarır
                        if (rand(1, 100) <= 15) {
                            $rows[] = $this->event('remove_from_cart', $attr, $ts->copy()->addMinutes(5), $product->id, $variant?->id, null, $price);
                        }

                        // %45 ödemeye ulaşır
                        if (rand(1, 100) <= 45) {
                            $total = $price * $qty + rand(0, 200);
                            $rows[] = $this->event('reached_checkout', $attr, $ts->copy()->addMinutes(7), null, null, null, $total);

                            // %60 satın alır
                            if (rand(1, 100) <= 60) {
                                $rows[] = $this->event('purchase', $attr, $ts->copy()->addMinutes(9), null, null, null, $total);
                            }
                        }
                    }
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            AnalyticsEvent::insert($chunk);
        }
    }

    private function event(string $type, array $attr, $ts, $productId = null, $variantId = null, $qty = null, $value = 0): array
    {
        return [
            'session_id' => $attr['session_id'],
            'type' => $type,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $qty,
            'value' => $value,
            'channel' => $attr['channel'],
            'source' => $attr['source'],
            'medium' => $attr['medium'],
            'device' => $attr['device'],
            'created_at' => $ts,
        ];
    }
}
