<?php

namespace App\Services\Mail;

use App\Enums\OrderStatus;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Settings\CheckoutSettings;
use App\Settings\GeneralSettings;
use App\Settings\ThemeSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sipariş e-postalarını admin panelindeki şablonlardan üretir.
 * Şablon gövdesindeki {{ placeholder }} alanlarını siparişten doldurur,
 * markalı layout ile sarar ve gönderir.
 */
class OrderMailService
{
    /** Sipariş durumuna karşılık gelen şablon anahtarı. */
    public static function keyForStatus(OrderStatus $status): ?string
    {
        return match ($status) {
            OrderStatus::Shipped => 'order_shipped',
            OrderStatus::Delivered => 'order_delivered',
            OrderStatus::Cancelled => 'order_cancelled',
            default => null,
        };
    }

    /** Duruma göre uygun şablonu gönderir (varsa ve aktifse). */
    public function sendForStatus(Order $order, OrderStatus $status): void
    {
        $key = self::keyForStatus($status);
        if ($key) {
            $this->send($order, $key);
        }
    }

    /**
     * Belirtilen şablonu gönderir.
     * @return bool Gönderildiyse true; şablon yok/pasif veya alıcı yoksa false.
     */
    public function send(Order $order, string $key, ?string $overrideTo = null): bool
    {
        $template = EmailTemplate::findByKey($key);
        $to = $overrideTo ?: $order->contact_email;
        // Önizleme (overrideTo) gönderiminde pasif şablon da gönderilebilsin.
        if (! $template || (! $template->is_enabled && ! $overrideTo) || blank($to)) {
            return false;
        }

        try {
            $order->loadMissing(['items', 'shipment']);
            $map = $this->placeholders($order);

            $subject = $this->replace($template->subject, $map);
            $heading = $this->replace($template->heading, $map);
            $body = $this->replace($template->body_html, $map);

            $html = view('emails.layout', [
                'subject' => $subject,
                'heading' => $heading,
                'preheader' => $subject,
                'slot' => $body,
            ])->render();

            Mail::to($to)->send(new TemplatedMail($subject, $html));

            return true;
        } catch (\Throwable $e) {
            Log::warning('OrderMailService gönderim hatası', ['key' => $key, 'order' => $order->order_number, 'e' => $e->getMessage()]);

            return false;
        }
    }

    /** Placeholder => değer eşlemesi. */
    private function placeholders(Order $order): array
    {
        $brand = app(ThemeSettings::class)->primary_color ?: '#316f2c';
        $ship = (array) $order->shipping_address;
        $shipment = $order->shipment;

        $orderUrl = route('account.order.show', $order);
        $trackingUrl = $shipment?->tracking_url;

        return [
            'site_name' => app(GeneralSettings::class)->site_name,
            'customer_name' => $ship['name'] ?? 'Değerli Müşterimiz',
            'order_number' => $order->order_number,
            'order_date' => optional($order->created_at)->translatedFormat('d F Y'),
            'order_url' => $orderUrl,
            'subtotal' => $this->money($order->subtotal),
            'shipping_cost' => $order->shipping_cost > 0 ? $this->money($order->shipping_cost) : 'Ücretsiz',
            'grand_total' => $this->money($order->grand_total),
            'payment_method' => $order->payment_method?->getLabel() ?? '—',
            'tracking_number' => $shipment?->tracking_number ?? '—',
            'tracking_url' => $trackingUrl ?? $orderUrl,
            'carrier' => $shipment?->carrier ?? '—',
            'shipping_address' => $this->formatAddress($ship),
            'delivery_info' => $this->deliveryInfo($order),

            // Blok (HTML) placeholder'lar
            'items_table' => $this->itemsTable($order),
            'order_button' => $this->button('Siparişimi Görüntüle', $orderUrl, $brand),
            'tracking_button' => $trackingUrl
                ? $this->button('Kargomu Takip Et', $trackingUrl, $brand)
                : '',
            'bank_details' => $this->bankDetails($order),
        ];
    }

    private function replace(string $text, array $map): string
    {
        foreach ($map as $key => $value) {
            $text = str_replace(['{{ ' . $key . ' }}', '{{' . $key . '}}'], (string) $value, $text);
        }

        return $text;
    }

    private function money($value): string
    {
        return '₺' . number_format((float) $value, 2, ',', '.');
    }

    private function formatAddress(array $a): string
    {
        $parts = array_filter([
            $a['name'] ?? null,
            $a['phone'] ?? null,
            trim(($a['address'] ?? '') . ' ' . ($a['district'] ?? '') . ' ' . ($a['city'] ?? '')),
        ]);

        return e(implode(' · ', $parts));
    }

    private function deliveryInfo(Order $order): string
    {
        if (! $order->delivery_date) {
            return '';
        }

        return 'Teslimat: ' . \Illuminate\Support\Carbon::parse($order->delivery_date)->translatedFormat('d F Y')
            . ' ' . e((string) $order->delivery_slot);
    }

    private function button(string $label, string $url, string $brand): string
    {
        return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px auto 6px;"><tr><td style="border-radius:10px;background:' . e($brand) . ';">'
            . '<a href="' . e($url) . '" style="display:inline-block;padding:13px 30px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">' . e($label) . '</a>'
            . '</td></tr></table>';
    }

    private function itemsTable(Order $order): string
    {
        $rows = '';
        foreach ($order->items as $item) {
            $qty = rtrim(rtrim(number_format((float) $item->quantity, 3, ',', '.'), '0'), ',');
            $variant = $item->variant_name ? ' <span style="color:#9aa69d;">(' . e($item->variant_name) . ')</span>' : '';
            $rows .= '<tr style="border-bottom:1px solid #eef1ed;">'
                . '<td style="padding:11px 0;font-size:14px;color:#37433b;">' . e($item->name) . $variant
                . '<br><span style="color:#9aa69d;font-size:12px;">' . $qty . ' × ' . $this->money($item->unit_price) . '</span></td>'
                . '<td style="padding:11px 0;text-align:right;font-size:14px;color:#37433b;white-space:nowrap;">' . $this->money($item->line_total) . '</td>'
                . '</tr>';
        }

        $shipping = $order->shipping_cost > 0 ? $this->money($order->shipping_cost) : 'Ücretsiz';
        $discount = $order->discount_total > 0
            ? '<tr><td style="padding:3px 0;color:#6b766e;font-size:14px;">İndirim</td><td style="padding:3px 0;text-align:right;color:#2f8f4e;font-size:14px;">-' . $this->money($order->discount_total) . '</td></tr>'
            : '';

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 4px;background:#f8faf7;border-radius:12px;padding:6px 16px;">'
            . $rows
            . '<tr><td style="padding:10px 0 3px;color:#6b766e;font-size:14px;">Ara Toplam</td><td style="padding:10px 0 3px;text-align:right;color:#37433b;font-size:14px;">' . $this->money($order->subtotal) . '</td></tr>'
            . '<tr><td style="padding:3px 0;color:#6b766e;font-size:14px;">Kargo</td><td style="padding:3px 0;text-align:right;color:#37433b;font-size:14px;">' . $shipping . '</td></tr>'
            . $discount
            . '<tr><td style="padding:9px 0 4px;font-weight:700;font-size:16px;color:#1f2a23;border-top:2px solid #e6ebe3;">Toplam</td><td style="padding:9px 0 4px;text-align:right;font-weight:700;font-size:16px;color:#1f2a23;border-top:2px solid #e6ebe3;">' . $this->money($order->grand_total) . '</td></tr>'
            . '</table>';
    }

    private function bankDetails(Order $order): string
    {
        if ($order->payment_method?->value !== 'bank_transfer') {
            return '';
        }

        $c = app(\App\Settings\PaymentSettings::class);

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;">'
            . '<tr><td style="padding:16px 18px;font-size:14px;color:#7c2d12;line-height:1.7;">'
            . '<strong style="font-size:15px;">Havale / EFT ile ödeme bekleniyor</strong><br>'
            . e($c->bank_name) . ' · ' . e($c->bank_account_holder) . '<br>'
            . 'IBAN: <strong>' . e($c->bank_iban) . '</strong><br>'
            . 'Açıklama: <strong>' . e($order->order_number) . '</strong>'
            . '</td></tr></table>';
    }
}
