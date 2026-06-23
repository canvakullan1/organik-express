@php($try = fn ($v) => '₺' . number_format((float) $v, 2, ',', '.'))
<!DOCTYPE html>
<html lang="tr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f4f7f2;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:#1f2a23;">
    <div style="max-width:600px;margin:0 auto;padding:24px;">
        <div style="background:#316f2c;color:#fff;border-radius:14px;padding:24px;text-align:center;">
            <h1 style="margin:0;font-size:22px;">Siparişiniz Alındı</h1>
            <p style="margin:8px 0 0;opacity:.9;">Sipariş No: <strong>{{ $order->order_number }}</strong></p>
        </div>

        <div style="background:#fff;border-radius:14px;padding:24px;margin-top:16px;">
            <p>Merhaba {{ $order->shipping_address['name'] ?? '' }},</p>
            <p>Siparişiniz için teşekkürler! Aşağıda sipariş özetiniz yer alıyor.</p>

            @if($order->payment_method?->value === 'bank_transfer')
                <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px;margin:14px 0;">
                    <strong>Havale/EFT ile ödeme bekleniyor.</strong><br>
                    @php($c = app(\App\Settings\PaymentSettings::class))
                    {{ $c->bank_name }} · {{ $c->bank_account_holder }}<br>
                    IBAN: <strong>{{ $c->bank_iban }}</strong><br>
                    Açıklama: <strong>{{ $order->order_number }}</strong>
                </div>
            @endif

            <table style="width:100%;border-collapse:collapse;margin-top:8px;">
                @foreach($order->items as $item)
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:8px 0;">{{ $item->name }} @if($item->variant_name)<span style="color:#888;">({{ $item->variant_name }})</span>@endif<br><span style="color:#888;font-size:13px;">{{ rtrim(rtrim(number_format($item->quantity,3,',','.'),'0'),',') }} × {{ $try($item->unit_price) }}</span></td>
                        <td style="padding:8px 0;text-align:right;white-space:nowrap;">{{ $try($item->line_total) }}</td>
                    </tr>
                @endforeach
            </table>

            <table style="width:100%;margin-top:12px;">
                <tr><td style="color:#666;">Ara Toplam</td><td style="text-align:right;">{{ $try($order->subtotal) }}</td></tr>
                <tr><td style="color:#666;">Kargo</td><td style="text-align:right;">{{ $order->shipping_cost > 0 ? $try($order->shipping_cost) : 'Ücretsiz' }}</td></tr>
                <tr><td style="font-weight:bold;font-size:17px;padding-top:6px;">Toplam</td><td style="text-align:right;font-weight:bold;font-size:17px;padding-top:6px;">{{ $try($order->grand_total) }}</td></tr>
            </table>

            <div style="margin-top:16px;color:#555;font-size:14px;">
                <strong>Teslimat Adresi:</strong><br>
                {{ $order->shipping_address['name'] ?? '' }} · {{ $order->shipping_address['phone'] ?? '' }}<br>
                {{ $order->shipping_address['address'] ?? '' }}, {{ $order->shipping_address['district'] ?? '' }}/{{ $order->shipping_address['city'] ?? '' }}
                @if($order->delivery_date)<br><strong>Teslimat:</strong> {{ \Illuminate\Support\Carbon::parse($order->delivery_date)->translatedFormat('d F Y') }} {{ $order->delivery_slot }}@endif
            </div>
        </div>

        <p style="text-align:center;color:#999;font-size:12px;margin-top:16px;">{{ app(\App\Settings\GeneralSettings::class)->site_name }} · Her hafta kapınıza taze organik</p>
    </div>
</body>
</html>
