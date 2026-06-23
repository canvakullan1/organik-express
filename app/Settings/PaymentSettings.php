<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    public bool $bank_transfer_enabled;
    public bool $test_gateway_enabled;     // demo kart (yerel test)

    // Havale / EFT banka bilgileri (müşteriye gösterilir + sipariş mailine yazılır)
    public ?string $bank_name;
    public ?string $bank_account_holder;
    public ?string $bank_iban;

    // iyzico
    public bool $iyzico_enabled;
    public bool $iyzico_sandbox;
    public ?string $iyzico_api_key;
    public ?string $iyzico_secret_key;

    // PayTR
    public bool $paytr_enabled;
    public bool $paytr_sandbox;
    public ?string $paytr_merchant_id;
    public ?string $paytr_merchant_key;
    public ?string $paytr_merchant_salt;

    public static function group(): string
    {
        return 'payment';
    }

    /** Sağlayıcı anahtarları şifreli saklanır. */
    public static function encrypted(): array
    {
        return [
            'iyzico_api_key', 'iyzico_secret_key',
            'paytr_merchant_key', 'paytr_merchant_salt',
        ];
    }
}
