<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Coupon\CouponService;
use App\Services\Loyalty\LoyaltyService;
use App\Settings\CheckoutSettings;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private CartService $cart,
        private CouponService $coupons,
        private LoyaltyService $loyalty,
    ) {
    }

    public function shippingCost(float $subtotal, string $paymentMethod): float
    {
        $threshold = (float) app(GeneralSettings::class)->free_shipping_threshold;
        $checkout = app(CheckoutSettings::class);

        $shipping = $subtotal >= $threshold ? 0.0 : (float) $checkout->shipping_cost;

        if ($paymentMethod === PaymentMethod::CashOnDelivery->value) {
            $shipping += (float) $checkout->cash_on_delivery_fee;
        }

        return round($shipping, 2);
    }

    /**
     * Sepetin tüm fiyatlandırmasını hesapla (kupon + puan dahil).
     *
     * @return array{subtotal:float,coupon:?\App\Models\Coupon,coupon_discount:float,loyalty_used:float,shipping:float,discount_total:float,grand_total:float,redeemable:float}
     */
    /**
     * Erken sipariş indirimi yüzdesi: adres bir teslimat bölgesi şehrindeyse,
     * seçilen elden-teslim bölgesi geçerliyse VE teslim tarihi o bölgenin en erken
     * teslim günüyse yapılandırılmış yüzde; aksi halde 0.
     */
    public function earlyDiscountPercent(?string $city, $deliveryDate, ?string $zoneName = null): int
    {
        $s = app(\App\Settings\CheckoutSettings::class);
        $pct = (int) ($s->early_order_discount_percent ?? 0);
        if ($pct <= 0 || ! $city || ! $deliveryDate || ! $zoneName) {
            return 0;
        }

        // Şehir bir teslimat bölgesi mi — Türkçe karakterleri ASCII'ye indirip karşılaştır.
        $norm = function ($v) {
            $v = strtr((string) $v, [
                'İ' => 'i', 'I' => 'i', 'ı' => 'i', 'Ş' => 's', 'ş' => 's', 'Ğ' => 'g', 'ğ' => 'g',
                'Ü' => 'u', 'ü' => 'u', 'Ö' => 'o', 'ö' => 'o', 'Ç' => 'c', 'ç' => 'c',
            ]);

            return mb_strtolower(trim($v), 'UTF-8');
        };
        $zoneCities = array_map($norm, (array) ($s->delivery_zone_cities ?? []));
        if (! in_array($norm($city), $zoneCities, true)) {
            return 0;
        }

        // Seçilen bölgenin teslim günleri (0=Pazar..6=Cumartesi)
        $zone = collect((array) ($s->delivery_zones ?? []))->first(fn ($z) => ($z['name'] ?? null) === $zoneName);
        $days = array_map('intval', (array) ($zone['days'] ?? []));
        if (empty($days)) {
            return 0;
        }

        // Bu bölge için en erken teslim günü (>= bugün + lead)
        $lead = max(0, (int) $s->delivery_lead_days);
        $earliest = null;
        for ($i = 0; $i < 21; $i++) {
            $cand = now()->addDays($lead + $i)->startOfDay();
            if (in_array((int) $cand->dayOfWeek, $days, true)) {
                $earliest = $cand;
                break;
            }
        }
        if (! $earliest) {
            return 0;
        }

        try {
            $d = \Illuminate\Support\Carbon::parse($deliveryDate)->startOfDay();
        } catch (\Throwable $e) {
            return 0;
        }

        return $d->equalTo($earliest) ? $pct : 0;
    }

    public function pricing(?User $user, string $paymentMethod, float $loyaltyRequested = 0, int $earlyPct = 0): array
    {
        // Ara toplam tüm satırlardan (varyant + kutu); kupon kapsamı yalnız ürün satırları.
        $subtotal = round((float) $this->cart->lines()->sum('line_total'), 2);
        $variantItems = $this->cart->items();

        // Kupon (oturumda)
        $couponDiscount = 0.0;
        $coupon = null;
        if ($code = session('coupon_code')) {
            $res = $this->coupons->evaluate($code, $subtotal, $user, $variantItems);
            if ($res['ok']) {
                $coupon = $res['coupon'];
                $couponDiscount = $res['discount'];
            } else {
                session()->forget('coupon_code'); // geçersizleşmişse temizle
            }
        }

        $afterCoupon = max(0, $subtotal - $couponDiscount);

        // Para puan
        $redeemable = $this->loyalty->maxRedeemable($user, $afterCoupon);
        $loyaltyUsed = round(min(max(0, $loyaltyRequested), $redeemable), 2);

        // Erken sipariş indirimi (ara toplam üzerinden)
        $earlyDiscount = $earlyPct > 0 ? round($subtotal * $earlyPct / 100, 2) : 0.0;

        $discountTotal = round($couponDiscount + $loyaltyUsed + $earlyDiscount, 2);
        $shipping = $this->shippingCost($subtotal, $paymentMethod);
        $grand = round(max(0, $subtotal - $discountTotal) + $shipping, 2);

        return [
            'subtotal' => $subtotal,
            'coupon' => $coupon,
            'coupon_discount' => $couponDiscount,
            'loyalty_used' => $loyaltyUsed,
            'early_discount' => $earlyDiscount,
            'early_pct' => $earlyPct,
            'redeemable' => $redeemable,
            'shipping' => $shipping,
            'discount_total' => $discountTotal,
            'grand_total' => $grand,
        ];
    }

    public function placeFromCart(
        ?User $user,
        Address $shipping,
        Address $billing,
        string $paymentMethod,
        array $delivery = [],
        ?string $note = null,
        float $loyaltyRequested = 0,
        ?string $guestEmail = null,
        int $earlyPct = 0,
    ): Order {
        return DB::transaction(function () use ($user, $shipping, $billing, $paymentMethod, $delivery, $note, $loyaltyRequested, $guestEmail, $earlyPct) {
            $lines = $this->cart->lines();
            if ($lines->isEmpty()) {
                throw new \RuntimeException('Sepetiniz boş.');
            }

            $p = $this->pricing($user, $paymentMethod, $loyaltyRequested, $earlyPct);
            $attr = (array) session('attribution', []);

            // Gateway anahtarını (iyzico/paytr/test/bank_transfer) PaymentMethod enum'una çevir.
            $methodValue = app(\App\Services\Payment\PaymentManager::class)->get($paymentMethod)?->method()?->value
                ?? PaymentMethod::CreditCard->value;

            $order = Order::create([
                'order_number' => Order::generateNumber(),
                'user_id' => $user?->id,
                'status' => OrderStatus::AwaitingPayment,
                'payment_status' => PaymentStatus::Pending,
                'payment_method' => $methodValue,
                'subtotal' => $p['subtotal'],
                'shipping_cost' => $p['shipping'],
                'discount_total' => $p['discount_total'],
                'coupon_code' => $p['coupon']?->code,
                'coupon_discount' => $p['coupon_discount'],
                'loyalty_used' => $p['loyalty_used'],
                'early_discount' => $p['early_discount'],
                'grand_total' => $p['grand_total'],
                'currency' => 'TRY',
                'contact_email' => $user?->email ?? $guestEmail,
                'contact_phone' => $shipping->phone,
                'shipping_address' => $shipping->toSnapshot(),
                'billing_address' => $billing->toSnapshot(),
                'delivery_date' => $delivery['date'] ?? null,
                'delivery_slot' => $delivery['slot'] ?? null,
                'customer_note' => $note,
                'agreed_distance_sale' => true,
                'agreed_preinfo' => true,
                'channel' => $attr['channel'] ?? 'direct',
                'source' => $attr['source'] ?? null,
                'medium' => $attr['medium'] ?? null,
                'ip' => request()->ip(),
            ]);

            foreach ($lines as $row) {
                if ($row['type'] === 'bundle') {
                    // Hazır kutu kalemi (ürün/varyant bağı yok)
                    $order->items()->create([
                        'product_id' => null,
                        'variant_id' => null,
                        'name' => $row['name'],
                        'variant_name' => 'Hazır Kutu',
                        'unit' => 'paket',
                        'unit_price' => $row['unit_price'],
                        'quantity' => $row['qty'],
                        'line_total' => $row['line_total'],
                        'is_weight_based' => false,
                    ]);

                    continue;
                }

                /** @var ProductVariant $variant */
                $variant = $row['variant'];
                $product = $row['product'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'name' => $product->name,
                    'variant_name' => $variant->name,
                    'unit' => $variant->unit?->value,
                    'unit_price' => $variant->price,
                    'quantity' => $row['qty'],
                    'line_total' => $row['line_total'],
                    'is_weight_based' => $row['is_weight_based'],
                ]);

                if ($variant->track_stock) {
                    $variant->decrement('stock', $row['qty']);
                }
            }

            $order->statusHistory()->create([
                'status' => OrderStatus::AwaitingPayment->value,
                'note' => 'Sipariş oluşturuldu.',
                'created_at' => now(),
            ]);

            return $order;
        });
    }
}
