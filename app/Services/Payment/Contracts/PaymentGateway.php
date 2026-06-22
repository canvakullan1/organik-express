<?php

namespace App\Services\Payment\Contracts;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\Payment\PaymentResult;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /** Benzersiz sürücü anahtarı (bank_transfer, test, iyzico, paytr). */
    public function key(): string;

    /** Vitrinde gösterilecek ödeme yöntemi. */
    public function method(): PaymentMethod;

    public function label(): string;

    public function description(): string;

    /** Panel ayarlarına göre kullanılabilir mi? */
    public function isAvailable(): bool;

    /** Ödemeyi başlat. */
    public function charge(Order $order, array $input = []): PaymentResult;

    /** 3DS / sağlayıcı geri dönüşünü doğrula (yönlendirmeli sürücüler için). */
    public function verifyCallback(Request $request, Order $order): PaymentResult;
}
