<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case BankTransfer = 'bank_transfer'; // Havale / EFT
    case CreditCard = 'credit_card';     // Kredi kartı (iyzico/PayTR 3D Secure)
    case CashOnDelivery = 'cash_on_delivery'; // Kapıda ödeme
    case Test = 'test';                  // Test/Demo kartı (yerel test)

    public function getLabel(): string
    {
        return match ($this) {
            self::BankTransfer => 'Havale / EFT',
            self::CreditCard => 'Kredi / Banka Kartı',
            self::CashOnDelivery => 'Kapıda Ödeme',
            self::Test => 'Test Kartı (Demo)',
        };
    }
}
