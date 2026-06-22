<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Mail\OrderPlacedMail;
use App\Models\Address;
use App\Models\Order;
use App\Models\Coupon;
use App\Services\Analytics\AnalyticsRecorder;
use App\Services\Cart\CartService;
use App\Services\Coupon\CouponService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentManager;
use App\Settings\CheckoutSettings;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cart,
        private OrderService $orders,
        private PaymentManager $payments,
        private AnalyticsRecorder $analytics,
        private CouponService $coupons,
        private LoyaltyService $loyalty,
        private \App\Services\Order\OrderFinalizer $finalizer,
    ) {
    }

    public function index()
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('success', 'Sepetiniz boş.');
        }

        $user = auth()->user();
        $addresses = $user->addresses()->get();
        if ($addresses->isEmpty()) {
            return redirect()->route('account.address.create', ['return' => 'checkout'])
                ->with('success', 'Devam etmek için bir teslimat adresi ekleyin.');
        }

        $checkout = app(CheckoutSettings::class);
        $pricing = $this->orders->pricing($user, 'bank_transfer', (float) old('loyalty_points', 0));
        $dates = collect(range(0, 6))->map(fn ($i) => now()->addDays($checkout->delivery_lead_days + $i));

        return view('storefront.checkout.index', [
            'items' => $this->cart->lines(),
            'pricing' => $pricing,
            'threshold' => (float) app(GeneralSettings::class)->free_shipping_threshold,
            'loyaltyBalance' => $this->loyalty->balance($user),
            'addresses' => $addresses,
            'defaultAddressId' => optional($addresses->firstWhere('is_default', true))->id ?? $addresses->first()->id,
            'methods' => $this->payments->available(),
            'deliveryDates' => $dates,
            'deliverySlots' => $checkout->delivery_slots,
        ]);
    }

    public function store(Request $request)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $available = $this->payments->available()->map->key()->all();

        $data = $request->validate([
            'shipping_address_id' => ['required', 'integer'],
            'billing_same' => ['boolean'],
            'billing_address_id' => ['nullable', 'integer', 'required_if:billing_same,0'],
            'delivery_date' => ['nullable', 'date'],
            'delivery_slot' => ['nullable', 'string'],
            'payment_method' => ['required', 'string', 'in:' . implode(',', $available)],
            'note' => ['nullable', 'string', 'max:500'],
            'agree' => ['accepted'],
            'card_number' => ['nullable', 'string'],
            'loyalty_points' => ['nullable', 'numeric', 'min:0'],
        ], [
            'shipping_address_id.required' => 'Teslimat adresi seçin.',
            'payment_method.required' => 'Ödeme yöntemi seçin.',
            'payment_method.in' => 'Geçersiz ödeme yöntemi.',
            'agree.accepted' => 'Mesafeli satış ve ön bilgilendirme sözleşmelerini onaylamalısınız.',
        ]);

        $user = auth()->user();
        $shipping = $user->addresses()->findOrFail($data['shipping_address_id']);
        $billing = $request->boolean('billing_same', true)
            ? $shipping
            : $user->addresses()->findOrFail($data['billing_address_id']);

        $order = $this->orders->placeFromCart(
            $user,
            $shipping,
            $billing,
            $data['payment_method'],
            ['date' => $data['delivery_date'] ?? null, 'slot' => $data['delivery_slot'] ?? null],
            $data['note'] ?? null,
            (float) ($data['loyalty_points'] ?? 0),
        );

        $gateway = $this->payments->get($data['payment_method']);
        $result = $gateway->charge($order, $request->only('card_number'));

        // Ödeme kaydı
        $order->payments()->create([
            'gateway' => $gateway->key(),
            'method' => $gateway->method()->value,
            'status' => $result->state === 'paid' ? PaymentStatus::Paid->value : PaymentStatus::Pending->value,
            'amount' => $order->grand_total,
            'currency' => 'TRY',
            'transaction_id' => $result->transactionId,
            'response' => $result->raw,
            'paid_at' => $result->state === 'paid' ? now() : null,
        ]);

        return match ($result->state) {
            'paid' => $this->finalizePaid($order),
            'pending' => $this->finalizePending($order),
            'redirect' => $this->redirectToGateway($order, $result->redirectUrl),
            default => $this->fail($order, $result->message ?? 'Ödeme başarısız.'),
        };
    }

    /** 3DS / sağlayıcı geri dönüşü. */
    public function callback(Request $request, string $gatewayKey)
    {
        $orderId = session('pending_order');
        $order = $orderId ? Order::find($orderId) : null;
        $gateway = $this->payments->get($gatewayKey);

        if (! $order || ! $gateway) {
            return redirect()->route('cart.index')->with('success', 'Ödeme oturumu bulunamadı.');
        }

        $result = $gateway->verifyCallback($request, $order);
        session()->forget('pending_order');

        if ($result->success && $result->state === 'paid') {
            $order->payments()->latest()->first()?->update([
                'status' => PaymentStatus::Paid->value,
                'transaction_id' => $result->transactionId,
                'paid_at' => now(),
            ]);

            return $this->finalizePaid($order);
        }

        // Başarısız 3DS: siparişi ödeme bekliyor bırak, kullanıcıyı bilgilendir.
        return redirect()->route('checkout.index')->withErrors(['payment' => $result->message ?? 'Ödeme tamamlanamadı.']);
    }

    public function success(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return view('storefront.checkout.success', ['order' => $order->load('items')]);
    }

    /* ---------------- yardımcılar ---------------- */

    private function finalizePaid(Order $order)
    {
        $this->finalizer->markPaidAndFinalize($order);
        $this->cleanup();

        return redirect()->route('checkout.success', $order);
    }

    private function finalizePending(Order $order)
    {
        $this->finalizer->finalizePending($order);
        $this->cleanup();

        return redirect()->route('checkout.success', $order);
    }

    private function redirectToGateway(Order $order, string $url)
    {
        session(['pending_order' => $order->id]);

        return redirect()->away($url);
    }

    private function fail(Order $order, string $message)
    {
        // Stok iadesi + siparişi geri al
        foreach ($order->items as $item) {
            if ($item->variant_id) {
                \App\Models\ProductVariant::where('id', $item->variant_id)->where('track_stock', true)
                    ->increment('stock', $item->quantity);
            }
        }
        $order->payments()->update(['status' => PaymentStatus::Failed->value]);
        $order->delete();

        return redirect()->route('checkout.index')->withErrors(['payment' => $message]);
    }

    /** Sipariş başarıyla verildikten sonra oturum temizliği. */
    private function cleanup(): void
    {
        $this->cart->clear();
        session()->forget('coupon_code');
        session()->forget('pending_order');
    }
}
