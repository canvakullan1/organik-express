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
use Illuminate\Support\Facades\URL;

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
        $guest = ! $user;

        $addresses = $user ? $user->addresses()->get() : collect();
        if ($user && $addresses->isEmpty()) {
            return redirect()->route('account.address.create', ['return' => 'checkout'])
                ->with('success', 'Devam etmek için bir teslimat adresi ekleyin.');
        }

        $checkout = app(CheckoutSettings::class);
        $pricing = $this->orders->pricing($user, 'bank_transfer', (float) old('loyalty_points', 0));

        // Aday teslim günleri (en erken günden itibaren 4 hafta) — bölgeye göre frontend filtreler.
        $dateCandidates = collect(range(0, 27))->map(function ($i) use ($checkout) {
            $d = now()->addDays($checkout->delivery_lead_days + $i);

            return [
                'date' => $d->format('Y-m-d'),
                'dow' => (int) $d->dayOfWeek, // 0=Pazar..6=Cumartesi
                'label' => $d->translatedFormat('d F Y, l'),
            ];
        })->values();

        return view('storefront.checkout.index', [
            'guest' => $guest,
            'items' => $this->cart->lines(),
            'pricing' => $pricing,
            'threshold' => (float) app(GeneralSettings::class)->free_shipping_threshold,
            'loyaltyBalance' => $this->loyalty->balance($user),
            'addresses' => $addresses,
            'defaultAddressId' => optional($addresses->firstWhere('is_default', true))->id ?? optional($addresses->first())->id,
            'methods' => $this->payments->available(),
            'dateCandidates' => $dateCandidates,
            'deliverySlots' => $checkout->delivery_slots,
            'deliveryInfoNote' => $checkout->delivery_info_note,
            'deliveryZones' => array_values((array) (json_decode($checkout->delivery_zones ?: '[]', true) ?: [])),
            'deliveryZoneCities' => array_values((array) $checkout->delivery_zone_cities),
            'earlyPct' => (int) $checkout->early_order_discount_percent,
        ]);
    }

    public function store(Request $request)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $available = $this->payments->available()->map->key()->all();
        $user = auth()->user();

        $commonMessages = [
            'payment_method.required' => 'Ödeme yöntemi seçin.',
            'payment_method.in' => 'Geçersiz ödeme yöntemi.',
            'agree.accepted' => 'Mesafeli satış ve ön bilgilendirme sözleşmelerini onaylamalısınız.',
        ];
        $commonRules = [
            'delivery_date' => ['nullable', 'date'],
            'delivery_slot' => ['nullable', 'string'],
            'delivery_zone' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['required', 'string', 'in:' . implode(',', $available)],
            'note' => ['nullable', 'string', 'max:500'],
            'agree' => ['accepted'],
            'card_number' => ['nullable', 'string'],
        ];

        $guestEmail = null;
        $loyaltyPoints = 0.0;

        if ($user) {
            // Üye: adres defterinden
            $data = $request->validate($commonRules + [
                'shipping_address_id' => ['required', 'integer'],
                'billing_same' => ['boolean'],
                'billing_address_id' => ['nullable', 'integer', 'required_if:billing_same,0'],
                'loyalty_points' => ['nullable', 'numeric', 'min:0'],
            ], $commonMessages + ['shipping_address_id.required' => 'Teslimat adresi seçin.']);

            $shipping = $user->addresses()->findOrFail($data['shipping_address_id']);
            $billing = $request->boolean('billing_same', true)
                ? $shipping
                : $user->addresses()->findOrFail($data['billing_address_id']);
            $loyaltyPoints = (float) ($data['loyalty_points'] ?? 0);
        } else {
            // Misafir: formdan adres (kaydedilmez, yalnız sipariş için)
            $data = $request->validate($commonRules + [
                'guest_email' => ['required', 'email:rfc', 'max:255'],
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'phone' => ['required', 'string', 'max:30'],
                'city' => ['required', 'string', 'max:100'],
                'district' => ['required', 'string', 'max:100'],
                'neighborhood' => ['nullable', 'string', 'max:150'],
                'address' => ['required', 'string', 'max:500'],
                'postal_code' => ['nullable', 'string', 'max:20'],
            ], $commonMessages + [
                'guest_email.required' => 'E-posta adresinizi girin.',
                'first_name.required' => 'Adınızı girin.',
                'phone.required' => 'Telefon numaranızı girin.',
                'address.required' => 'Açık adresinizi girin.',
            ]);

            $shipping = new Address([
                'title' => 'Teslimat',
                'is_corporate' => false,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'city' => $data['city'],
                'district' => $data['district'],
                'neighborhood' => $data['neighborhood'] ?? null,
                'address' => $data['address'],
                'postal_code' => $data['postal_code'] ?? null,
            ]);
            $billing = $shipping;
            $guestEmail = $data['guest_email'];
        }

        // Erken sipariş indirimi: adres teslimat bölgesinde + teslim tarihi yarın ise
        $earlyPct = $this->orders->earlyDiscountPercent($shipping->city, $data['delivery_date'] ?? null, $data['delivery_zone'] ?? null);

        $order = $this->orders->placeFromCart(
            $user,
            $shipping,
            $billing,
            $data['payment_method'],
            ['date' => $data['delivery_date'] ?? null, 'slot' => $data['delivery_slot'] ?? null],
            $data['note'] ?? null,
            $loyaltyPoints,
            $guestEmail,
            $earlyPct,
        );

        // Misafir siparişine sonuç sayfasında erişebilmek için oturuma yaz
        if (! $user) {
            $request->session()->push('guest_orders', $order->id);
        }

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

    public function success(Request $request, Order $order)
    {
        // İmzalı URL (session'dan bağımsız), üyelik veya misafir oturumu ile erişim
        $owns = $request->hasValidSignature()
            || (auth()->check() && $order->user_id === auth()->id())
            || in_array($order->id, (array) session('guest_orders', []), true);
        abort_unless($owns, 403);

        return view('storefront.checkout.success', ['order' => $order->load('items')]);
    }

    /* ---------------- yardımcılar ---------------- */

    /** Session'dan bağımsız çalışan imzalı sipariş-sonucu URL'i (6 saat geçerli). */
    private function successUrl(Order $order): string
    {
        return URL::temporarySignedRoute('checkout.success', now()->addHours(6), $order);
    }

    private function finalizePaid(Order $order)
    {
        $this->finalizer->markPaidAndFinalize($order);
        $this->cleanup();

        return redirect()->to($this->successUrl($order));
    }

    private function finalizePending(Order $order)
    {
        $this->finalizer->finalizePending($order);
        $this->cleanup();

        return redirect()->to($this->successUrl($order));
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
