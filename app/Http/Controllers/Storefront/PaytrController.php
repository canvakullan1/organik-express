<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderFinalizer;
use App\Services\Payment\Gateways\PaytrGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaytrController extends Controller
{
    /**
     * PayTR sunucu-sunucu bildirimi (Bildirim URL: {APP_URL}/odeme/paytr/notify).
     * Ödemenin gerçek doğrulaması burada yapılır; yanıt olarak düz "OK" döner.
     */
    public function notify(Request $request, PaytrGateway $gateway, OrderFinalizer $finalizer)
    {
        $post = $request->all();

        if (! $gateway->verifyNotification($post)) {
            Log::warning('PayTR notify: hash uyuşmadı', $post);

            return response('PAYTR notification failed: bad hash', 400);
        }

        $payment = Payment::where('reference', $post['merchant_oid'] ?? '')->latest()->first();
        $order = $payment?->order;

        if (! $order) {
            return response('OK'); // sipariş yoksa da PayTR'ı tekrar denemekten kurtar
        }

        if (($post['status'] ?? '') === 'success') {
            $payment->update(['status' => PaymentStatus::Paid->value, 'transaction_id' => $post['merchant_oid'], 'paid_at' => now()]);
            $finalizer->markPaidAndFinalize($order);
        } else {
            $payment->update(['status' => PaymentStatus::Failed->value, 'response' => $post]);
            Log::info('PayTR ödeme başarısız', ['oid' => $post['merchant_oid'] ?? '', 'reason' => $post['failed_reason_msg'] ?? '']);
        }

        return response('OK');
    }

    /** Kullanıcının PayTR sonrası döndüğü sayfa (onay sunucu tarafında yapıldı). */
    public function return(Request $request)
    {
        $orderId = session('pending_order');
        $order = $orderId ? Order::find($orderId) : null;

        // Sepet temizliği (oturum burada mevcut)
        app(\App\Services\Cart\CartService::class)->clear();
        session()->forget(['pending_order', 'coupon_code']);

        if ($order && $order->user_id === auth()->id()) {
            return redirect()->route('checkout.success', $order);
        }

        return redirect()->route('home')->with('success', 'Ödeme işleminiz alındı. Siparişlerinizden takip edebilirsiniz.');
    }
}
