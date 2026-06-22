<?php

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Gateways\BankTransferGateway;
use App\Services\Payment\Gateways\IyzicoGateway;
use App\Services\Payment\Gateways\PaytrGateway;
use App\Services\Payment\Gateways\TestGateway;
use Illuminate\Support\Collection;

class PaymentManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    private const GATEWAYS = [
        'bank_transfer' => BankTransferGateway::class,
        'iyzico' => IyzicoGateway::class,
        'paytr' => PaytrGateway::class,
        'test' => TestGateway::class,
    ];

    public function get(string $key): ?PaymentGateway
    {
        $class = self::GATEWAYS[$key] ?? null;

        return $class ? app($class) : null;
    }

    /** Vitrinde gösterilecek, panelden aktif edilmiş ödeme yöntemleri. */
    public function available(): Collection
    {
        return collect(self::GATEWAYS)
            ->map(fn ($class) => app($class))
            ->filter(fn (PaymentGateway $g) => $g->isAvailable())
            ->values();
    }
}
