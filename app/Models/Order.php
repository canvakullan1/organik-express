<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'status', 'payment_status', 'payment_method',
        'subtotal', 'shipping_cost', 'discount_total', 'grand_total', 'currency',
        'coupon_code', 'coupon_discount', 'loyalty_used', 'loyalty_earned', 'early_discount',
        'contact_email', 'contact_phone', 'shipping_address', 'billing_address',
        'delivery_date', 'delivery_slot', 'customer_note',
        'agreed_distance_sale', 'agreed_preinfo',
        'channel', 'source', 'medium', 'ip', 'paid_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'loyalty_used' => 'decimal:2',
        'loyalty_earned' => 'decimal:2',
        'early_discount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'delivery_date' => 'date',
        'agreed_distance_sale' => 'boolean',
        'agreed_preinfo' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('created_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    /** Benzersiz sipariş numarası üret. */
    public static function generateNumber(): string
    {
        do {
            $number = 'ORD-' . now()->format('ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    /** Durum değiştir + geçmişe yaz + (durum gerçekten değiştiyse) müşteriye bildirim maili. */
    public function changeStatus(OrderStatus $status, ?string $note = null, ?int $userId = null): void
    {
        $changed = $this->status !== $status;

        $this->update(['status' => $status]);
        $this->statusHistory()->create([
            'status' => $status->value,
            'note' => $note,
            'created_by' => $userId,
            'created_at' => now(),
        ]);

        if ($changed) {
            // Şablon (kargoya verildi / teslim edildi / iptal) varsa otomatik gönderir.
            app(\App\Services\Mail\OrderMailService::class)->sendForStatus($this, $status);
        }
    }

    public function markPaid(): void
    {
        $this->update([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    /**
     * Adres anlik goruntusunu (JSON) okunakli metne cevirir.
     *
     * Filament'te `TextEntry::make('shipping_address')` + `formatStateUsing()` calismaz:
     * state bir DIZI oldugu icin Filament onu "coklu deger" sanip formatlayiciyi her
     * eleman icin ayri cagirir, `is_array($state)` false doner ve panelde adres "-"
     * gorunur (mail'de dogru cikar, cunku Blade dizide dogrudan gezinir).
     * Bu yuzden infolist `->state(fn ($record) => $record->addressText(...))` kullanir.
     */
    public function addressText(?array $a): string
    {
        if (empty($a)) {
            return '—';
        }

        $lines = [$a['name'] ?? null];

        if (! empty($a['is_corporate'])) {
            $lines[] = trim(($a['company_name'] ?? '') . ' (Kurumsal)');
            $tax = array_filter([$a['tax_office'] ?? null, $a['tax_number'] ?? null]);
            if ($tax) {
                $lines[] = 'VD/VKN: ' . implode(' / ', $tax);
            }
        }

        $lines[] = $a['phone'] ?? null;
        $lines[] = trim(implode(' ', array_filter([$a['neighborhood'] ?? null, $a['address'] ?? null])));

        $cityLine = implode(' / ', array_filter([$a['district'] ?? null, $a['city'] ?? null]));
        if (! empty($a['postal_code'])) {
            $cityLine = trim($cityLine . ' ' . $a['postal_code']);
        }
        $lines[] = $cityLine;

        $lines = array_values(array_filter($lines, fn ($l) => trim((string) $l) !== ''));

        return $lines ? implode("
", $lines) : '—';
    }
}
