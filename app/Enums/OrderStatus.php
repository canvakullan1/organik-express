<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasLabel, HasColor
{
    case AwaitingPayment = 'awaiting_payment'; // Ödeme bekliyor (havale)
    case Pending = 'pending';                   // Onay bekliyor (ödeme alındı)
    case Preparing = 'preparing';               // Hazırlanıyor
    case Shipped = 'shipped';                    // Kargoda
    case Delivered = 'delivered';                // Teslim edildi
    case Cancelled = 'cancelled';                // İptal
    case Refunded = 'refunded';                  // İade

    public function getLabel(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'Ödeme Bekliyor',
            self::Pending => 'Onay Bekliyor',
            self::Preparing => 'Hazırlanıyor',
            self::Shipped => 'Kargoda',
            self::Delivered => 'Teslim Edildi',
            self::Cancelled => 'İptal Edildi',
            self::Refunded => 'İade Edildi',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::AwaitingPayment => 'warning',
            self::Pending => 'info',
            self::Preparing => 'primary',
            self::Shipped => 'info',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
            self::Refunded => 'gray',
        };
    }
}
