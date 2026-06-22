<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Checkout
        $this->migrator->add('checkout.shipping_cost', 49.90);
        $this->migrator->add('checkout.cash_on_delivery_enabled', false);
        $this->migrator->add('checkout.cash_on_delivery_fee', 25.00);
        $this->migrator->add('checkout.bank_name', 'Banka adı (admin panelinden girin)');
        $this->migrator->add('checkout.bank_account_holder', 'Organik Ürün Ltd. Şti.');
        $this->migrator->add('checkout.bank_iban', 'TR00 0000 0000 0000 0000 0000 00');
        $this->migrator->add('checkout.delivery_lead_days', 1);
        $this->migrator->add('checkout.delivery_slots', [
            '09:00 - 12:00',
            '12:00 - 15:00',
            '15:00 - 18:00',
            '18:00 - 21:00',
        ]);

        // Payment
        $this->migrator->add('payment.bank_transfer_enabled', true);
        $this->migrator->add('payment.test_gateway_enabled', true);
        $this->migrator->add('payment.iyzico_enabled', false);
        $this->migrator->add('payment.iyzico_sandbox', true);
        $this->migrator->addEncrypted('payment.iyzico_api_key', '');
        $this->migrator->addEncrypted('payment.iyzico_secret_key', '');
        $this->migrator->add('payment.paytr_enabled', false);
        $this->migrator->add('payment.paytr_sandbox', true);
        $this->migrator->add('payment.paytr_merchant_id', '');
        $this->migrator->addEncrypted('payment.paytr_merchant_key', '');
        $this->migrator->addEncrypted('payment.paytr_merchant_salt', '');
    }
};
