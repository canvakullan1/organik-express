<?php

namespace App\Services\Loyalty;

use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\User;
use App\Settings\LoyaltySettings;

class LoyaltyService
{
    public function balance(User $user): float
    {
        return round((float) $user->loyaltyTransactions()->sum('points'), 2);
    }

    /** Bu sepet için kullanılabilecek en yüksek puan. */
    public function maxRedeemable(User $user, float $subtotal): float
    {
        $s = app(LoyaltySettings::class);
        if (! $s->enabled) {
            return 0;
        }

        $balance = $this->balance($user);
        if ($balance < $s->min_balance_to_redeem) {
            return 0;
        }

        $cap = floor($subtotal * $s->max_redeem_percent / 100);

        return (float) max(0, min($balance, $cap));
    }

    /** Puan kullan (sipariş indirimi). */
    public function redeem(User $user, float $points, ?Order $order = null): void
    {
        if ($points <= 0) {
            return;
        }

        $this->record($user, -$points, 'redeem', $order, 'Siparişte kullanıldı' . ($order ? " ({$order->order_number})" : ''));
    }

    /** Ödenen sipariş için puan kazandır. */
    public function award(Order $order): void
    {
        $s = app(LoyaltySettings::class);
        if (! $s->enabled || ! $order->user_id) {
            return;
        }

        // Aynı sipariş için tekrar kazanım verme
        if (LoyaltyTransaction::where('order_id', $order->id)->where('type', 'earn')->exists()) {
            return;
        }

        if ((float) $order->subtotal < $s->min_order_to_earn) {
            return;
        }

        $points = round((float) $order->subtotal * $s->earn_rate / 100, 2);
        if ($points <= 0) {
            return;
        }

        $this->record($order->user, $points, 'earn', $order, 'Siparişten kazanıldı (' . $order->order_number . ')');
        $order->update(['loyalty_earned' => $points]);
    }

    public function adjust(User $user, float $points, string $note): void
    {
        $this->record($user, $points, 'adjust', null, $note);
    }

    private function record(User $user, float $points, string $type, ?Order $order, string $desc): void
    {
        $balance = $this->balance($user) + $points;

        LoyaltyTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order?->id,
            'type' => $type,
            'points' => $points,
            'balance_after' => $balance,
            'description' => $desc,
            'created_at' => now(),
        ]);
    }
}
