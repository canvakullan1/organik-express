<!DOCTYPE html>
<html lang="tr">
<head><meta charset="utf-8"></head>
<body style="margin:0;background:#f3f4f6;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#1f2a23;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;">
        <tr><td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;">
                <tr><td style="background:#16a34a;padding:20px 28px;color:#fff;font-size:18px;font-weight:700;">
                    📩 Yeni İletişim Mesajı
                </td></tr>
                <tr><td style="padding:26px 28px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;line-height:1.7;">
                        <tr><td style="padding:6px 0;color:#6b766e;width:110px;">Ad Soyad</td><td style="padding:6px 0;font-weight:600;">{{ $senderName }}</td></tr>
                        <tr><td style="padding:6px 0;color:#6b766e;">E-posta</td><td style="padding:6px 0;"><a href="mailto:{{ $senderEmail }}" style="color:#16a34a;">{{ $senderEmail }}</a></td></tr>
                        @if($senderPhone)
                        <tr><td style="padding:6px 0;color:#6b766e;">Telefon</td><td style="padding:6px 0;"><a href="tel:{{ $senderPhone }}" style="color:#16a34a;">{{ $senderPhone }}</a></td></tr>
                        @endif
                    </table>
                    <div style="margin-top:18px;padding:16px 18px;background:#f8faf7;border:1px solid #e6ebe3;border-radius:10px;white-space:pre-wrap;font-size:15px;line-height:1.7;color:#37433b;">{{ $messageBody }}</div>
                    <p style="margin:20px 0 0;color:#9aa69d;font-size:13px;">Bu mesaja doğrudan "Yanıtla" ile cevap verebilirsiniz — cevap müşteriye gider.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
