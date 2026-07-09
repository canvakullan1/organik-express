<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Mail\OrderPlacedMail;
use App\Models\AnalyticsEvent;
use App\Models\Coupon;
use App\Models\Order;
use App\Services\Analytics\AnalyticsRecorder;
use App\Services\Coupon\CouponService;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Support\Facades\Mail;

/**
 * Sipariş tamamlama (kupon/puan tüketimi, satın alma analitiği, onay maili).
 * Idempotent: aynı sipariş için bir kez işler. Oturumdan bağımsızdır,
 * bu yüzden PayTR sunucu-sunucu bildiriminde de güvenle kullanılır.
 */
class OrderFinalizer
{
    public function __construct(
        private CouponService $coupons,
        private LoyaltyService $loyalty,
        private AnalyticsRecorder $analytics,
    ) {
    }

    /** Ödeme alındı → ödendi işaretle, durum güncelle, puan kazandır, ortak adımlar. */
    public function markPaidAndFinalize(Order $order): void
    {
        if (! $order->isPaid()) {
            $order->markPaid();
            $order->changeStatus(OrderStatus::Pending, 'Ödeme alındı.');
        }

        $this->common($order);

        if ($order->user) {
            $this->loyalty->award($order);
        }
    }

    /** Havale gibi ödeme bekleyen siparişlerde ortak adımlar (puan kazanımı yok). */
    public function finalizePending(Order $order): void
    {
        $this->common($order);
    }

    /** Bir kez: kupon kullanımı + puan düşümü + analitik + mail. */
    private function common(Order $order): void
    {
        // Satın alma olayı zaten varsa daha önce işlenmiştir → çık.
        if (AnalyticsEvent::where('order_id', $order->id)->where('type', 'purchase')->exists()) {
            return;
        }

        if ($order->coupon_code && ($coupon = Coupon::where('code', $order->coupon_code)->first())) {
            $this->coupons->recordUsage($coupon, $order->user, $order->id, (float) $order->coupon_discount);
        }

        if ((float) $order->loyalty_used > 0 && $order->user) {
            $this->loyalty->redeem($order->user, (float) $order->loyalty_used, $order);
        }

        $this->analytics->recordOrderPurchase($order);

        try {
            // Önce admin panelinden yönetilen şablonu dene; yoksa/pasifse klasik maile düş.
            $sent = app(\App\Services\Mail\OrderMailService::class)->send($order, 'order_placed');
            if (! $sent) {
                Mail::to($order->contact_email)->send(new OrderPlacedMail($order));
            }

            // Yönetici bildirimi: yeni sipariş iletisim@organikexpress.com'a düşsün.
            if ($adminTo = config('mail.admin_notifications')) {
                Mail::to($adminTo)->send(new OrderPlacedMail($order, forAdmin: true));
            }
        } catch (\Throwable) {
            // mail hatası siparişi engellemesin
        }
    }
}
