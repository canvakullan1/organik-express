<?php

namespace App\Services\Payment\Gateways;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\PaymentResult;
use App\Settings\PaymentSettings;
use Illuminate\Http\Request;

/**
 * Test / Demo kartı — gerçek tahsilat yapmadan ödeme akışını yerel olarak test etmek için.
 * Kart numarası 4111 1111 1111 1111 → başarılı, diğerleri → başarısız (simülasyon).
 */
class TestGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'test';
    }

    public function method(): PaymentMethod
    {
        return PaymentMethod::Test;
    }

    public function label(): string
    {
        return 'Test Kartı (Demo)';
    }

    public function description(): string
    {
        return 'Gerçek tahsilat yapılmaz. Başarılı için kart no: 4111 1111 1111 1111';
    }

    public function isAvailable(): bool
    {
        return app(PaymentSettings::class)->test_gateway_enabled;
    }

    public function charge(Order $order, array $input = []): PaymentResult
    {
        $card = preg_replace('/\s+/', '', (string) ($input['card_number'] ?? ''));

        if ($card === '4111111111111111') {
            return PaymentResult::paid('TEST-' . strtoupper(bin2hex(random_bytes(6))), ['simulated' => true]);
        }

        return PaymentResult::failed('Test kartı reddedildi. Başarılı test için 4111 1111 1111 1111 kullanın.');
    }

    public function verifyCallback(Request $request, Order $order): PaymentResult
    {
        return PaymentResult::paid('TEST-CALLBACK');
    }
}
