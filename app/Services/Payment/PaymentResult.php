<?php

namespace App\Services\Payment;

/**
 * Ödeme başlatma sonucu.
 * state: paid (tamamlandı) | redirect (3DS'e yönlendir) | pending (manuel, ör. havale) | failed
 */
class PaymentResult
{
    public function __construct(
        public bool $success,
        public string $state,
        public ?string $redirectUrl = null,
        public ?string $transactionId = null,
        public ?string $message = null,
        public array $raw = [],
    ) {
    }

    public static function paid(?string $transactionId = null, array $raw = []): self
    {
        return new self(true, 'paid', null, $transactionId, null, $raw);
    }

    public static function redirect(string $url, array $raw = []): self
    {
        return new self(true, 'redirect', $url, null, null, $raw);
    }

    public static function pending(?string $message = null, array $raw = []): self
    {
        return new self(true, 'pending', null, null, $message, $raw);
    }

    public static function failed(string $message, array $raw = []): self
    {
        return new self(false, 'failed', null, null, $message, $raw);
    }
}
