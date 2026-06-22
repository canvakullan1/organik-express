@php
    $general = app(\App\Settings\GeneralSettings::class);
    $theme = app(\App\Settings\ThemeSettings::class);
    $contact = app(\App\Settings\ContactSettings::class);
    $social = app(\App\Settings\SocialSettings::class);
    $brand = $theme->primary_color ?: '#316f2c';
    $accent = $theme->accent_color ?: '#c2703d';
    $logo = $general->logo ? asset('storage/' . $general->logo) : null;
    $year = now()->format('Y');
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject ?? $general->site_name }}</title>
</head>
<body style="margin:0;padding:0;background:#eef2ec;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2a23;-webkit-font-smoothing:antialiased;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $preheader ?? $general->tagline }}</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2ec;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;">

                    {{-- Başlık / logo --}}
                    <tr>
                        <td style="padding:0 4px 18px;text-align:center;">
                            @if($logo)
                                <img src="{{ $logo }}" alt="{{ $general->site_name }}" height="40" style="height:40px;width:auto;display:inline-block;">
                            @else
                                <span style="font-size:22px;font-weight:700;letter-spacing:-0.02em;color:{{ $brand }};">{{ $general->site_name }}</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Üst marka şeridi --}}
                    <tr>
                        <td style="background:{{ $brand }};border-radius:16px 16px 0 0;padding:30px 32px;text-align:center;">
                            @isset($heading)
                                <h1 style="margin:0;font-size:23px;line-height:1.25;color:#ffffff;font-weight:700;">{{ $heading }}</h1>
                            @endisset
                            @isset($subheading)
                                <p style="margin:10px 0 0;font-size:14px;color:rgba(255,255,255,0.88);">{!! $subheading !!}</p>
                            @endisset
                        </td>
                    </tr>

                    {{-- İçerik --}}
                    <tr>
                        <td style="background:#ffffff;padding:32px;border-radius:0 0 16px 16px;font-size:15px;line-height:1.65;color:#37433b;">
                            {!! $slot !!}
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 16px 8px;text-align:center;">
                            @php
                                $links = array_filter([
                                    'Instagram' => $social->instagram ?? null,
                                    'Facebook' => $social->facebook ?? null,
                                    'X' => $social->x ?? null,
                                    'YouTube' => $social->youtube ?? null,
                                ]);
                            @endphp
                            @if($links)
                                <p style="margin:0 0 10px;">
                                    @foreach($links as $label => $url)
                                        <a href="{{ $url }}" style="color:{{ $brand }};text-decoration:none;font-size:13px;font-weight:600;margin:0 8px;">{{ $label }}</a>
                                    @endforeach
                                </p>
                            @endif
                            <p style="margin:0;font-size:13px;font-weight:600;color:#37433b;">{{ $general->site_name }}</p>
                            <p style="margin:4px 0 0;font-size:12px;color:#8a968d;">{{ $general->tagline }}</p>
                            @if($contact->phone || $contact->email)
                                <p style="margin:8px 0 0;font-size:12px;color:#8a968d;">
                                    @if($contact->phone){{ $contact->phone }}@endif
                                    @if($contact->phone && $contact->email) · @endif
                                    @if($contact->email){{ $contact->email }}@endif
                                </p>
                            @endif
                            <p style="margin:12px 0 0;font-size:11px;color:#aab4ac;">© {{ $year }} {{ $general->site_name }}. Bu e-posta siparişinizle ilgili otomatik gönderilmiştir.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
