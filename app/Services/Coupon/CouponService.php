<?php

namespace App\Services\Coupon;

use App\Enums\DiscountType;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use Illuminate\Support\Collection;

class CouponService
{
    /**
     * Kuponu doğrula ve indirim tutarını hesapla.
     *
     * @param Collection<int, array> $items  CartService::items() çıktısı
     * @return array{ok:bool, message:?string, coupon:?Coupon, discount:float}
     */
    public function evaluate(string $code, float $subtotal, ?User $user, Collection $items): array
    {
        $coupon = Coupon::where('code', strtoupper(trim($code)))->first();

        if (! $coupon || ! $coupon->is_active) {
            return $this->fail('Kupon kodu geçersiz.');
        }

        $now = now();
        if ($coupon->starts_at && $coupon->starts_at->gt($now)) {
            return $this->fail('Kupon henüz aktif değil.');
        }
        if ($coupon->ends_at && $coupon->ends_at->lt($now)) {
            return $this->fail('Kuponun süresi dolmuş.');
        }
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return $this->fail('Kupon kullanım limiti dolmuş.');
        }
        if ($subtotal < (float) $coupon->min_subtotal) {
            return $this->fail('Bu kupon için minimum sepet tutarı ₺' . number_format($coupon->min_subtotal, 2, ',', '.') . '.');
        }
        if ($user && $coupon->per_user_limit !== null) {
            $used = CouponUsage::where('coupon_id', $coupon->id)->where('user_id', $user->id)->count();
            if ($used >= $coupon->per_user_limit) {
                return $this->fail('Bu kuponu kullanım hakkınız doldu.');
            }
        }

        $base = $this->applicableBase($coupon, $subtotal, $items);
        if ($base <= 0) {
            return $this->fail('Kupon sepetinizdeki ürünlere uygulanamıyor.');
        }

        $discount = $coupon->type === DiscountType::Percent
            ? $base * (float) $coupon->value / 100
            : min((float) $coupon->value, $base);

        if ($coupon->type === DiscountType::Percent && $coupon->max_discount) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        $discount = round(min($discount, $subtotal), 2);

        return ['ok' => true, 'message' => null, 'coupon' => $coupon, 'discount' => $discount];
    }

    /** Kapsamdaki ürünlerin toplam tutarı (indirimin uygulanacağı taban). */
    private function applicableBase(Coupon $coupon, float $subtotal, Collection $items): float
    {
        if ($coupon->scope === 'all' || empty($coupon->scope_ids)) {
            return $subtotal;
        }

        $ids = array_map('intval', $coupon->scope_ids);

        return (float) $items->filter(function ($row) use ($coupon, $ids) {
            $product = $row['product'];

            return $coupon->scope === 'category'
                ? in_array((int) $product->category_id, $ids, true)
                : in_array((int) $product->id, $ids, true);
        })->sum('line_total');
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message, 'coupon' => null, 'discount' => 0.0];
    }

    public function recordUsage(Coupon $coupon, ?User $user, int $orderId, float $discount): void
    {
        $coupon->increment('used_count');
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user?->id,
            'order_id' => $orderId,
            'discount' => $discount,
            'created_at' => now(),
        ]);
    }
}
