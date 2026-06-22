<?php

namespace App\Services\Wishlist;

use App\Models\Product;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;

/**
 * Session tabanlı favoriler / istek listesi (misafir dahil).
 * Üye girişinde ileride DB'ye taşınabilir.
 */
class WishlistService
{
    private const SESSION_KEY = 'wishlist';

    public function __construct(private SessionManager $session)
    {
    }

    public function toggle(int $productId): bool
    {
        $ids = $this->ids();

        if (in_array($productId, $ids, true)) {
            $this->session->put(self::SESSION_KEY, array_values(array_diff($ids, [$productId])));

            return false; // çıkarıldı
        }

        $ids[] = $productId;
        $this->session->put(self::SESSION_KEY, array_values(array_unique($ids)));

        return true; // eklendi
    }

    public function remove(int $productId): void
    {
        $this->session->put(self::SESSION_KEY, array_values(array_diff($this->ids(), [$productId])));
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    /** @return Collection<int, Product> */
    public function products(): Collection
    {
        $ids = $this->ids();

        if (empty($ids)) {
            return collect();
        }

        return Product::active()
            ->with(['images', 'variants', 'category'])
            ->whereIn('id', $ids)
            ->get();
    }

    /** @return array<int, int> */
    public function ids(): array
    {
        return array_map('intval', (array) $this->session->get(self::SESSION_KEY, []));
    }
}
