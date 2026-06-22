<?php

namespace App\Services\Cart;

use App\Models\Bundle;
use App\Models\ProductVariant;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;

/**
 * Session tabanlı sepet. İki tür satır içerir:
 *  - Varyant (ürün): session 'cart' = [variant_id => qty]
 *  - Kutu (bundle):  session 'cart_bundles' = [bundle_id => qty]
 * Fiyatlar KDV dahildir. Ağırlık bazlı ürünlerde tutar tahminîdir.
 */
class CartService
{
    private const KEY = 'cart';
    private const KEY_BUNDLES = 'cart_bundles';

    public function __construct(private SessionManager $session)
    {
    }

    /* ---------------- Varyant işlemleri ---------------- */

    public function add(int $variantId, float $qty = 1): void
    {
        $items = $this->rawVariants();
        $items[$variantId] = ($items[$variantId] ?? 0) + $qty;
        $this->session->put(self::KEY, $items);
    }

    public function update(int $variantId, float $qty): void
    {
        $items = $this->rawVariants();
        if ($qty <= 0) {
            unset($items[$variantId]);
        } else {
            $items[$variantId] = $qty;
        }
        $this->session->put(self::KEY, $items);
    }

    public function remove(int $variantId): void
    {
        $items = $this->rawVariants();
        unset($items[$variantId]);
        $this->session->put(self::KEY, $items);
    }

    /* ---------------- Kutu işlemleri ---------------- */

    public function addBundle(int $bundleId, float $qty = 1): void
    {
        $items = $this->rawBundles();
        $items[$bundleId] = ($items[$bundleId] ?? 0) + $qty;
        $this->session->put(self::KEY_BUNDLES, $items);
    }

    public function updateBundle(int $bundleId, float $qty): void
    {
        $items = $this->rawBundles();
        if ($qty <= 0) {
            unset($items[$bundleId]);
        } else {
            $items[$bundleId] = $qty;
        }
        $this->session->put(self::KEY_BUNDLES, $items);
    }

    public function removeBundle(int $bundleId): void
    {
        $items = $this->rawBundles();
        unset($items[$bundleId]);
        $this->session->put(self::KEY_BUNDLES, $items);
    }

    /* ---------------- Genel ---------------- */

    public function clear(): void
    {
        $this->session->forget([self::KEY, self::KEY_BUNDLES]);
    }

    public function count(): int
    {
        return (int) (collect($this->rawVariants())->sum() + collect($this->rawBundles())->sum());
    }

    public function isEmpty(): bool
    {
        return empty($this->rawVariants()) && empty($this->rawBundles());
    }

    /** Geriye dönük uyumluluk: yalnız varyant satırları. */
    public function items(): Collection
    {
        return $this->lines()->where('type', 'variant')->values();
    }

    /**
     * Birleşik sepet satırları (varyant + kutu).
     *
     * @return Collection<int, array>
     */
    public function lines(): Collection
    {
        $lines = collect();

        // Varyantlar
        $rawV = $this->rawVariants();
        if (! empty($rawV)) {
            $variants = ProductVariant::with(['product.images'])->whereIn('id', array_keys($rawV))->get()->keyBy('id');
            foreach ($rawV as $id => $qty) {
                $v = $variants->get($id);
                if (! $v || ! $v->product) {
                    continue;
                }
                $lines->push([
                    'type' => 'variant',
                    'id' => (int) $id,
                    'name' => $v->product->name,
                    'sub' => $v->name ?: trim($v->unit_amount . ' ' . ($v->unit?->value ?? '')),
                    'cover' => $v->product->cover_url,
                    'unit_price' => (float) $v->price,
                    'qty' => (float) $qty,
                    'line_total' => round($v->price * $qty, 2),
                    'is_weight_based' => (bool) $v->is_weight_based,
                    'url' => route('product.show', $v->product->slug),
                    'product' => $v->product,
                    'variant' => $v,
                    'bundle' => null,
                ]);
            }
        }

        // Kutular
        $rawB = $this->rawBundles();
        if (! empty($rawB)) {
            $bundles = Bundle::whereIn('id', array_keys($rawB))->get()->keyBy('id');
            foreach ($rawB as $id => $qty) {
                $b = $bundles->get($id);
                if (! $b || ! $b->is_active) {
                    continue;
                }
                $lines->push([
                    'type' => 'bundle',
                    'id' => (int) $id,
                    'name' => $b->name,
                    'sub' => 'Hazır kutu',
                    'cover' => $b->image_url,
                    'unit_price' => (float) $b->price,
                    'qty' => (float) $qty,
                    'line_total' => round($b->price * $qty, 2),
                    'is_weight_based' => false,
                    'url' => route('bundle.show', $b->slug),
                    'product' => null,
                    'variant' => null,
                    'bundle' => $b,
                ]);
            }
        }

        return $lines->values();
    }

    public function subtotal(): float
    {
        return (float) $this->lines()->sum('line_total');
    }

    public function hasWeightBasedItems(): bool
    {
        return $this->lines()->contains('is_weight_based', true);
    }

    /** @return array<int, float> */
    private function rawVariants(): array
    {
        return (array) $this->session->get(self::KEY, []);
    }

    /** @return array<int, float> */
    private function rawBundles(): array
    {
        return (array) $this->session->get(self::KEY_BUNDLES, []);
    }
}
