<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $tpl) {
            EmailTemplate::updateOrCreate(['key' => $tpl['key']], $tpl);
        }
    }

    private function meta(): string
    {
        return <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
    <tr>
        <td style="font-size:13px;color:#6b766e;">Sipariş No</td>
        <td style="font-size:13px;color:#6b766e;text-align:right;">Tarih</td>
    </tr>
    <tr>
        <td style="font-size:15px;font-weight:700;color:#1f2a23;">{{ order_number }}</td>
        <td style="font-size:15px;font-weight:700;color:#1f2a23;text-align:right;">{{ order_date }}</td>
    </tr>
</table>
HTML;
    }

    private function address(): string
    {
        return <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 0;border-top:1px solid #eef1ed;">
    <tr><td style="padding-top:16px;font-size:13px;color:#9aa69d;text-transform:uppercase;letter-spacing:0.04em;font-weight:700;">Teslimat Adresi</td></tr>
    <tr><td style="padding-top:6px;font-size:14px;color:#37433b;line-height:1.6;">{{ shipping_address }}<br>{{ delivery_info }}</td></tr>
</table>
HTML;
    }

    private function templates(): array
    {
        $meta = $this->meta();
        $address = $this->address();

        return [
            [
                'key' => 'order_placed',
                'name' => 'Sipariş Alındı',
                'subject' => 'Siparişiniz alındı — {{ order_number }}',
                'heading' => 'Siparişiniz Alındı',
                'is_enabled' => true,
                'body_html' => <<<HTML
<p style="margin:0 0 14px;font-size:16px;">Merhaba <strong>{{ customer_name }}</strong>,</p>
<p style="margin:0 0 20px;color:#5b665d;">Siparişiniz başarıyla alındı. Hazırlanıp kargoya verildiğinde sizi tekrar bilgilendireceğiz. Bizi tercih ettiğiniz için teşekkür ederiz.</p>
{$meta}
{{ bank_details }}
<p style="margin:18px 0 8px;font-size:14px;font-weight:700;color:#1f2a23;">Sipariş Özeti</p>
{{ items_table }}
{{ order_button }}
{$address}
HTML,
            ],
            [
                'key' => 'order_shipped',
                'name' => 'Kargoya Verildi',
                'subject' => 'Siparişiniz yola çıktı — {{ order_number }}',
                'heading' => 'Siparişiniz Yola Çıktı',
                'is_enabled' => true,
                'body_html' => <<<HTML
<p style="margin:0 0 14px;font-size:16px;">Merhaba <strong>{{ customer_name }}</strong>,</p>
<p style="margin:0 0 20px;color:#5b665d;">Güzel haber! <strong>{{ order_number }}</strong> numaralı siparişiniz kargoya teslim edildi ve yola çıktı.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 4px;background:#f1f7f0;border-radius:12px;">
    <tr><td style="padding:16px 18px;font-size:14px;color:#37433b;line-height:1.8;">
        <span style="color:#6b766e;">Kargo Firması:</span> <strong>{{ carrier }}</strong><br>
        <span style="color:#6b766e;">Takip Numarası:</span> <strong>{{ tracking_number }}</strong>
    </td></tr>
</table>
{{ tracking_button }}
<p style="margin:22px 0 8px;font-size:14px;font-weight:700;color:#1f2a23;">Sipariş Özeti</p>
{{ items_table }}
{$address}
HTML,
            ],
            [
                'key' => 'order_delivered',
                'name' => 'Teslim Edildi',
                'subject' => 'Siparişiniz teslim edildi — {{ order_number }}',
                'heading' => 'Siparişiniz Teslim Edildi',
                'is_enabled' => true,
                'body_html' => <<<HTML
<p style="margin:0 0 14px;font-size:16px;">Merhaba <strong>{{ customer_name }}</strong>,</p>
<p style="margin:0 0 20px;color:#5b665d;"><strong>{{ order_number }}</strong> numaralı siparişiniz teslim edildi. Afiyet olsun! Ürünlerimizi beğeneceğinizi umuyoruz.</p>
<p style="margin:0 0 20px;color:#5b665d;">Deneyiminizi bizimle paylaşır mısınız? Görüşleriniz hem bizim hem de diğer müşterilerimiz için çok değerli.</p>
{{ order_button }}
<p style="margin:22px 0 8px;font-size:14px;font-weight:700;color:#1f2a23;">Sipariş Özeti</p>
{{ items_table }}
HTML,
            ],
            [
                'key' => 'order_cancelled',
                'name' => 'Sipariş İptal',
                'subject' => 'Siparişiniz iptal edildi — {{ order_number }}',
                'heading' => 'Siparişiniz İptal Edildi',
                'is_enabled' => true,
                'body_html' => <<<HTML
<p style="margin:0 0 14px;font-size:16px;">Merhaba <strong>{{ customer_name }}</strong>,</p>
<p style="margin:0 0 20px;color:#5b665d;"><strong>{{ order_number }}</strong> numaralı siparişiniz iptal edilmiştir. Ödemeniz alındıysa, iadeniz en kısa sürede ödeme yönteminize iade edilecektir.</p>
<p style="margin:0 0 20px;color:#5b665d;">Bir sorun mu yaşadınız veya yardıma mı ihtiyacınız var? Bize ulaşmaktan çekinmeyin, memnuniyetle yardımcı oluruz.</p>
<p style="margin:18px 0 8px;font-size:14px;font-weight:700;color:#1f2a23;">İptal Edilen Sipariş</p>
{{ items_table }}
{{ order_button }}
HTML,
            ],
            [
                'key' => 'order_payment_reminder',
                'name' => 'Havale Ödeme Hatırlatma',
                'subject' => 'Ödemenizi bekliyoruz — {{ order_number }}',
                'heading' => 'Ödemeniz Bekleniyor',
                'is_enabled' => true,
                'body_html' => <<<HTML
<p style="margin:0 0 14px;font-size:16px;">Merhaba <strong>{{ customer_name }}</strong>,</p>
<p style="margin:0 0 20px;color:#5b665d;"><strong>{{ order_number }}</strong> numaralı siparişiniz için henüz havale/EFT ödemenizi alamadık. Siparişinizi hazırlayabilmemiz için ödemenizi tamamlamanızı rica ederiz.</p>
{{ bank_details }}
<p style="margin:0 0 8px;font-size:14px;font-weight:700;color:#1f2a23;">Sipariş Özeti</p>
{{ items_table }}
{{ order_button }}
<p style="margin:18px 0 0;color:#8a968d;font-size:13px;">Ödemenizi yakın zamanda yaptıysanız bu mesajı dikkate almayınız. Sorularınız için bize ulaşabilirsiniz.</p>
HTML,
            ],
        ];
    }
}
