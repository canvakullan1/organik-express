<?php

namespace App\Services\Payment\Gateways;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\PaymentResult;
use App\Settings\PaymentSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\CheckoutForm;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\Currency;
use Iyzipay\Model\Locale;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Request\RetrieveCheckoutFormRequest;

/**
 * iyzico Checkout Form (3D Secure, hosted) entegrasyonu.
 * Anahtarlar admin → Ödeme Ayarları'ndan girilir; girilince otomatik aktif olur.
 * Kart verisi iyzico'nun kendi sayfasında girilir (PCI-DSS dışında kalırız).
 */
class IyzicoGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'iyzico';
    }

    public function method(): PaymentMethod
    {
        return PaymentMethod::CreditCard;
    }

    public function label(): string
    {
        return 'Kredi / Banka Kartı (iyzico)';
    }

    public function description(): string
    {
        return '3D Secure ile güvenli ödeme · taksit seçenekleri';
    }

    public function isAvailable(): bool
    {
        $s = app(PaymentSettings::class);

        return $s->iyzico_enabled && filled($s->iyzico_api_key) && filled($s->iyzico_secret_key);
    }

    private function options(): Options
    {
        $s = app(PaymentSettings::class);
        $options = new Options();
        $options->setApiKey($s->iyzico_api_key);
        $options->setSecretKey($s->iyzico_secret_key);
        $options->setBaseUrl($s->iyzico_sandbox ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com');

        return $options;
    }

    private function money($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    public function charge(Order $order, array $input = []): PaymentResult
    {
        try {
            $request = new CreateCheckoutFormInitializeRequest();
            $request->setLocale(Locale::TR);
            $request->setConversationId($order->order_number);
            $request->setPrice($this->money($order->subtotal));      // sepet kalemleri toplamı
            $request->setPaidPrice($this->money($order->grand_total)); // tahsil edilecek (kargo/indirim dahil)
            $request->setCurrency(Currency::TL);
            $request->setBasketId($order->order_number);
            $request->setPaymentGroup(PaymentGroup::PRODUCT);
            $request->setCallbackUrl(route('checkout.callback', 'iyzico'));
            $request->setEnabledInstallments([1, 2, 3, 6, 9]);

            $ship = (array) $order->shipping_address;
            $bill = (array) $order->billing_address;
            [$name, $surname] = $this->splitName($ship['name'] ?? 'Müşteri');

            $buyer = new Buyer();
            $buyer->setId((string) ($order->user_id ?? $order->id));
            $buyer->setName($name);
            $buyer->setSurname($surname);
            $buyer->setGsmNumber($this->gsm($order->contact_phone));
            $buyer->setEmail($order->contact_email ?: 'musteri@organik.test');
            $buyer->setIdentityNumber('11111111111'); // TC zorunlu; test/placeholder
            $buyer->setRegistrationAddress($ship['address'] ?? 'Adres');
            $buyer->setIp($order->ip ?: request()->ip());
            $buyer->setCity($ship['city'] ?? 'İstanbul');
            $buyer->setCountry('Turkey');
            $buyer->setZipCode($ship['postal_code'] ?? '34000');
            $request->setBuyer($buyer);

            $request->setShippingAddress($this->address($ship));
            $request->setBillingAddress($this->address($bill ?: $ship));

            $items = [];
            foreach ($order->items as $item) {
                $bi = new BasketItem();
                $bi->setId((string) $item->id);
                $bi->setName($item->name);
                $bi->setCategory1('Organik Ürün');
                $bi->setItemType(BasketItemType::PHYSICAL);
                $bi->setPrice($this->money($item->line_total));
                $items[] = $bi;
            }
            $request->setBasketItems($items);

            $result = CheckoutFormInitialize::create($request, $this->options());

            if ($result->getStatus() === 'success') {
                $order->payments()->latest()->first()?->update(['reference' => $result->getToken()]);

                return PaymentResult::redirect($result->getPaymentPageUrl(), ['token' => $result->getToken()]);
            }

            Log::warning('iyzico initialize failed', ['msg' => $result->getErrorMessage(), 'order' => $order->order_number]);

            return PaymentResult::failed($result->getErrorMessage() ?: 'iyzico ödeme başlatılamadı.');
        } catch (\Throwable $e) {
            Log::error('iyzico exception', ['e' => $e->getMessage()]);

            return PaymentResult::failed('iyzico bağlantı hatası: ' . $e->getMessage());
        }
    }

    public function verifyCallback(Request $request, Order $order): PaymentResult
    {
        try {
            $token = $request->input('token');
            if (! $token) {
                return PaymentResult::failed('Ödeme jetonu bulunamadı.');
            }

            $retrieve = new RetrieveCheckoutFormRequest();
            $retrieve->setLocale(Locale::TR);
            $retrieve->setConversationId($order->order_number);
            $retrieve->setToken($token);

            $form = CheckoutForm::retrieve($retrieve, $this->options());

            if ($form->getStatus() === 'success' && $form->getPaymentStatus() === 'SUCCESS') {
                return PaymentResult::paid($form->getPaymentId(), ['raw' => 'success']);
            }

            return PaymentResult::failed($form->getErrorMessage() ?: 'Ödeme tamamlanamadı.');
        } catch (\Throwable $e) {
            return PaymentResult::failed('iyzico doğrulama hatası: ' . $e->getMessage());
        }
    }

    private function address(array $a): Address
    {
        $address = new Address();
        $address->setContactName($a['name'] ?? 'Müşteri');
        $address->setCity($a['city'] ?? 'İstanbul');
        $address->setCountry('Turkey');
        $address->setAddress($a['address'] ?? 'Adres');
        $address->setZipCode($a['postal_code'] ?? '34000');

        return $address;
    }

    private function splitName(string $full): array
    {
        $parts = preg_split('/\s+/', trim($full));
        $surname = count($parts) > 1 ? array_pop($parts) : 'Müşteri';

        return [implode(' ', $parts) ?: 'Müşteri', $surname];
    }

    private function gsm(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }
        if (! str_starts_with($digits, '90')) {
            $digits = '90' . $digits;
        }

        return '+' . $digits;
    }
}
