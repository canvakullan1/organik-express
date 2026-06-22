<?php

namespace App\Services\Payment\Gateways;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\PaymentResult;
use App\Settings\PaymentSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayTR iFrame API entegrasyonu.
 * Anahtarlar (merchant_id/key/salt) admin → Ödeme Ayarları'ndan girilir.
 * Ödeme sonucu, PayTR mağaza panelinde tanımlanacak "Bildirim URL" (notify)
 * üzerinden sunucu-sunucu doğrulanır: {APP_URL}/odeme/paytr/notify
 */
class PaytrGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'paytr';
    }

    public function method(): PaymentMethod
    {
        return PaymentMethod::CreditCard;
    }

    public function label(): string
    {
        return 'Kredi / Banka Kartı (PayTR)';
    }

    public function description(): string
    {
        return '3D Secure ile güvenli ödeme · taksit seçenekleri';
    }

    public function isAvailable(): bool
    {
        $s = app(PaymentSettings::class);

        return $s->paytr_enabled
            && filled($s->paytr_merchant_id)
            && filled($s->paytr_merchant_key)
            && filled($s->paytr_merchant_salt);
    }

    /** Sipariş numarasından PayTR uyumlu (alfanümerik) sipariş kimliği. */
    public static function oid(Order $order): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $order->order_number);
    }

    public function charge(Order $order, array $input = []): PaymentResult
    {
        $s = app(PaymentSettings::class);

        $merchantId = $s->paytr_merchant_id;
        $merchantKey = $s->paytr_merchant_key;
        $merchantSalt = $s->paytr_merchant_salt;

        $oid = self::oid($order);
        $email = $order->contact_email ?: 'musteri@organik.test';
        $amount = (int) round((float) $order->grand_total * 100); // kuruş
        $ip = $order->ip ?: request()->ip();
        $currency = 'TL';
        $testMode = $s->paytr_sandbox ? '1' : '0';
        $noInstallment = '0';
        $maxInstallment = '0';

        $basket = [];
        foreach ($order->items as $item) {
            $basket[] = [$item->name, number_format((float) $item->unit_price, 2, '.', ''), (int) max(1, round($item->quantity))];
        }
        $userBasket = base64_encode(json_encode($basket));

        $hashStr = $merchantId . $ip . $oid . $email . $amount . $userBasket . $noInstallment . $maxInstallment . $currency . $testMode;
        $paytrToken = base64_encode(hash_hmac('sha256', $hashStr . $merchantSalt, $merchantKey, true));

        $ship = (array) $order->shipping_address;

        $payload = [
            'merchant_id' => $merchantId,
            'user_ip' => $ip,
            'merchant_oid' => $oid,
            'email' => $email,
            'payment_amount' => $amount,
            'paytr_token' => $paytrToken,
            'user_basket' => $userBasket,
            'debug_on' => $s->paytr_sandbox ? 1 : 0,
            'no_installment' => $noInstallment,
            'max_installment' => $maxInstallment,
            'user_name' => $ship['name'] ?? 'Müşteri',
            'user_address' => $ship['address'] ?? 'Adres',
            'user_phone' => $order->contact_phone ?: '05000000000',
            'merchant_ok_url' => route('checkout.paytr.return'),
            'merchant_fail_url' => route('checkout.paytr.return'),
            'timeout_limit' => 30,
            'currency' => $currency,
            'test_mode' => $testMode,
        ];

        try {
            $response = Http::asForm()->post('https://www.paytr.com/odeme/api/get-token', $payload);
            $json = $response->json();

            if (($json['status'] ?? null) === 'success') {
                $order->payments()->latest()->first()?->update(['reference' => $oid]);

                return PaymentResult::redirect('https://www.paytr.com/odeme/guvenli/' . $json['token'], ['oid' => $oid]);
            }

            Log::warning('PayTR get-token failed', ['reason' => $json['reason'] ?? $response->body(), 'order' => $order->order_number]);

            return PaymentResult::failed('PayTR ödeme başlatılamadı: ' . ($json['reason'] ?? 'bilinmeyen hata'));
        } catch (\Throwable $e) {
            Log::error('PayTR exception', ['e' => $e->getMessage()]);

            return PaymentResult::failed('PayTR bağlantı hatası: ' . $e->getMessage());
        }
    }

    /** Kullanıcı geri dönüşü değil; onay sunucu-sunucu notify ile yapılır. */
    public function verifyCallback(Request $request, Order $order): PaymentResult
    {
        return $order->isPaid() ? PaymentResult::paid() : PaymentResult::pending();
    }

    /** PayTR bildirim hash doğrulaması (sunucu-sunucu). */
    public function verifyNotification(array $post): bool
    {
        $s = app(PaymentSettings::class);
        $hash = base64_encode(hash_hmac(
            'sha256',
            ($post['merchant_oid'] ?? '') . $s->paytr_merchant_salt . ($post['status'] ?? '') . ($post['total_amount'] ?? ''),
            $s->paytr_merchant_key,
            true
        ));

        return hash_equals($hash, (string) ($post['hash'] ?? ''));
    }
}
