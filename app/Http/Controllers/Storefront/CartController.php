<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\Analytics\AnalyticsRecorder;
use App\Services\Cart\CartService;
use App\Services\Coupon\CouponService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cart,
        private AnalyticsRecorder $analytics,
        private CouponService $coupons,
    ) {
    }

    public function index()
    {
        $subtotal = $this->cart->subtotal();
        $items = $this->cart->items();

        // Oturumdaki kuponu doğrula
        $couponDiscount = 0.0;
        $couponCode = session('coupon_code');
        if ($couponCode) {
            $res = $this->coupons->evaluate($couponCode, $subtotal, auth()->user(), $items);
            if ($res['ok']) {
                $couponDiscount = $res['discount'];
            } else {
                session()->forget('coupon_code');
                $couponCode = null;
            }
        }

        return view('storefront.cart', [
            'lines' => $this->cart->lines(),
            'subtotal' => $subtotal,
            'couponCode' => $couponCode,
            'couponDiscount' => $couponDiscount,
            'hasWeightBased' => $this->cart->hasWeightBasedItems(),
        ]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'qty' => ['nullable', 'numeric', 'min:0.001'],
        ]);

        $variant = ProductVariant::findOrFail($data['variant_id']);
        abort_unless($variant->is_active, 422);

        $qty = (float) ($data['qty'] ?? 1);
        $this->cart->add($variant->id, $qty);

        $this->analytics->record('add_to_cart', [
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => $qty,
            'value' => (float) $variant->price * $qty,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Ürün sepete eklendi.',
                'count' => $this->cart->count(),
            ]);
        }

        return back()->with('success', 'Ürün sepete eklendi.');
    }

    /** Sepete hazır kutu ekle. */
    public function addBundle(Request $request)
    {
        $data = $request->validate([
            'bundle_id' => ['required', 'integer', 'exists:bundles,id'],
            'qty' => ['nullable', 'numeric', 'min:1'],
        ]);

        $bundle = \App\Models\Bundle::findOrFail($data['bundle_id']);
        abort_unless($bundle->is_active, 422);

        $qty = (float) ($data['qty'] ?? 1);
        $this->cart->addBundle($bundle->id, $qty);

        $this->analytics->record('add_to_cart', [
            'quantity' => $qty,
            'value' => (float) $bundle->price * $qty,
        ]);

        return back()->with('success', $bundle->name . ' sepete eklendi.');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:variant,bundle'],
            'variant_id' => ['required', 'integer'],
            'qty' => ['required', 'numeric', 'min:0'],
        ]);

        if (($data['type'] ?? 'variant') === 'bundle') {
            $this->cart->updateBundle((int) $data['variant_id'], (float) $data['qty']);
        } else {
            $this->cart->update((int) $data['variant_id'], (float) $data['qty']);
        }

        return back()->with('success', 'Sepet güncellendi.');
    }

    public function remove(Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:variant,bundle'],
            'variant_id' => ['required', 'integer'],
        ]);
        $id = (int) $data['variant_id'];

        if (($data['type'] ?? 'variant') === 'bundle') {
            $this->cart->removeBundle($id);
            $this->analytics->record('remove_from_cart', ['value' => 0]);

            return back()->with('success', 'Kutu sepetten çıkarıldı.');
        }

        $variant = ProductVariant::find($id);
        $this->cart->remove($id);

        $this->analytics->record('remove_from_cart', [
            'product_id' => $variant?->product_id,
            'variant_id' => $id,
            'value' => (float) ($variant->price ?? 0),
        ]);

        return back()->with('success', 'Ürün sepetten çıkarıldı.');
    }

    public function applyCoupon(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50']], [
            'code.required' => 'Kupon kodu girin.',
        ]);

        $res = $this->coupons->evaluate(
            $data['code'],
            $this->cart->subtotal(),
            auth()->user(),
            $this->cart->items()
        );

        if (! $res['ok']) {
            return back()->withErrors(['coupon' => $res['message']]);
        }

        session(['coupon_code' => $res['coupon']->code]);

        return back()->with('success', 'Kupon uygulandı: -₺' . number_format($res['discount'], 2, ',', '.'));
    }

    public function removeCoupon()
    {
        session()->forget('coupon_code');

        return back()->with('success', 'Kupon kaldırıldı.');
    }

    /** Ödemeye geçme niyeti — analitik olayı + checkout'a yönlendirme. */
    public function checkout()
    {
        $this->analytics->record('reached_checkout', [
            'value' => $this->cart->subtotal(),
        ]);

        // Üyeliksiz (misafir) alışveriş açık — giriş zorunlu değil.
        return redirect()->route('checkout.index');
    }
}
