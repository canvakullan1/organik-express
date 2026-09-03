<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Olayları, oturumda saklı atıf (attribution) verisiyle birlikte kaydeder.
 * Atıf ilk-dokunuş mantığıyla oturum başında set edilir (TrackVisit middleware).
 */
class AnalyticsRecorder
{
    public function __construct(private Request $request)
    {
    }

    /**
     * @param array{product_id?:int,variant_id?:int,order_id?:int,quantity?:float,value?:float} $data
     */
    public function record(string $type, array $data = []): void
    {
        $attr = (array) session('attribution', []);

        try {
            AnalyticsEvent::create([
                'session_id' => session()->getId(),
                'user_id' => Auth::id(),
                'type' => $type,
                'product_id' => $data['product_id'] ?? null,
                'variant_id' => $data['variant_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'quantity' => $data['quantity'] ?? null,
                'value' => $data['value'] ?? 0,
                'channel' => $attr['channel'] ?? 'direct',
                'source' => $attr['source'] ?? null,
                'medium' => $attr['medium'] ?? null,
                'campaign' => $attr['campaign'] ?? null,
                'term' => $attr['term'] ?? null,
                'content' => $attr['content'] ?? null,
                'referrer' => $this->clip($attr['referrer'] ?? null),
                'landing_page' => $this->clip($attr['landing_page'] ?? null),
                'url' => $this->clip($this->request->fullUrl()),
                'device' => $this->detectDevice(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Analitik kaydı hiçbir zaman ziyaretçinin isteğini bozmamalı.
            report($e);
        }
    }


    /** Uzun reklam URL'lerini sütun sınırına göre kırpar (kayıt patlamasın). */
    private function clip(?string $value, int $max = 2000): ?string
    {
        return $value === null ? null : mb_substr($value, 0, $max);
    }

    /**
     * Siparişten doğrudan satın alma olayı (oturumdan bağımsız —
     * PayTR sunucu-sunucu bildirimi gibi durumlar için).
     */
    public function recordOrderPurchase(\App\Models\Order $order): void
    {
        if (\App\Models\AnalyticsEvent::where('order_id', $order->id)->where('type', 'purchase')->exists()) {
            return; // çift kayıt önle
        }

        try {
            \App\Models\AnalyticsEvent::create([
                'session_id' => 'order-' . $order->id,
                'user_id' => $order->user_id,
                'type' => 'purchase',
                'order_id' => $order->id,
                'value' => (float) $order->grand_total,
                'channel' => $order->channel ?: 'direct',
                'source' => $this->clip($order->source, 250),
                'medium' => $this->clip($order->medium, 250),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Sipariş tamamlanırken analitik hatası akışı bozmamalı.
            report($e);
        }
    }

    private function detectDevice(): string
    {
        $ua = strtolower((string) $this->request->userAgent());

        return match (true) {
            str_contains($ua, 'ipad') || str_contains($ua, 'tablet') => 'tablet',
            str_contains($ua, 'mobi') || str_contains($ua, 'android') || str_contains($ua, 'iphone') => 'mobile',
            default => 'desktop',
        };
    }
}
