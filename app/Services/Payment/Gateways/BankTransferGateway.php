<?php

namespace App\Services\Payment\Gateways;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\PaymentResult;
use App\Settings\CheckoutSettings;
use App\Settings\PaymentSettings;
use Illuminate\Http\Request;

/**
 * Havale / EFT — çevrim içi tahsilat yok. Sipariş "ödeme bekliyor" olarak açılır,
 * müşteriye banka bilgileri gösterilir, admin ödemeyi manuel onaylar.
 */
class BankTransferGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'bank_transfer';
    }

    public function method(): PaymentMethod
    {
        return PaymentMethod::BankTransfer;
    }

    public function label(): string
    {
        return 'Havale / EFT';
    }

    public function description(): string
    {
        $bank = app(PaymentSettings::class);

        return 'Sipariş sonrası IBAN bilgilerimize ödeme yaparak siparişinizi tamamlayın.'
            . ($bank->bank_name ? ' (' . $bank->bank_name . ')' : '');
    }

    public function isAvailable(): bool
    {
        return app(PaymentSettings::class)->bank_transfer_enabled;
    }

    public function charge(Order $order, array $input = []): PaymentResult
    {
        // Tahsilat yok; sipariş ödeme bekliyor durumunda kalır.
        return PaymentResult::pending('Havale bilgileri sipariş özetinde gösterilecek.');
    }

    public function verifyCallback(Request $request, Order $order): PaymentResult
    {
        return PaymentResult::pending();
    }
}
