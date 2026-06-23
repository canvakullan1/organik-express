<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Havale/EFT banka bilgilerini checkout grubundan payment grubuna taşı (değerler korunur)
        $this->migrator->rename('checkout.bank_name', 'payment.bank_name');
        $this->migrator->rename('checkout.bank_account_holder', 'payment.bank_account_holder');
        $this->migrator->rename('checkout.bank_iban', 'payment.bank_iban');
    }

    public function down(): void
    {
        $this->migrator->rename('payment.bank_name', 'checkout.bank_name');
        $this->migrator->rename('payment.bank_account_holder', 'checkout.bank_account_holder');
        $this->migrator->rename('payment.bank_iban', 'checkout.bank_iban');
    }
};
