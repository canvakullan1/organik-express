@php
    // Admin'in seçtiği 2 renkten (marka + aksan) tam tonlama skalası üretiyoruz.
    // Tailwind v4 utility'leri var(--color-leaf-XXX) referansı kullandığından,
    // bu değişkenleri runtime'da ezmek tüm siteyi canlı olarak yeniden renklendirir.
    $primary = $theme->primary_color ?: '#316f2c';
    $accent  = $theme->accent_color ?: '#b45a34';

    // [shade => [yüzde, taban-renk]]  taban: w=white, b=black
    $scale = [
        50 => [8, 'w'], 100 => [15, 'w'], 200 => [28, 'w'], 300 => [45, 'w'],
        400 => [65, 'w'], 500 => [82, 'w'], 600 => [100, 'w'],
        700 => [88, 'b'], 800 => [72, 'b'], 900 => [60, 'b'], 950 => [35, 'b'],
    ];

    $emit = function (string $name, string $color) use ($scale) {
        $lines = [];
        foreach ($scale as $shade => [$pct, $base]) {
            if ($pct === 100 && $base === 'w') {
                $lines[] = "--color-{$name}-{$shade}: {$color};";
            } else {
                $mixWith = $base === 'w' ? 'white' : 'black';
                $lines[] = "--color-{$name}-{$shade}: color-mix(in srgb, {$color} {$pct}%, {$mixWith});";
            }
        }
        return implode("\n  ", $lines);
    };

    $heading = $theme->heading_font ?: 'Fraunces';
    $body = $theme->body_font ?: 'Plus Jakarta Sans';

    // Google Fonts URL'i için font ailelerini topla (benzersiz)
    $families = collect([$heading, $body])->unique()->map(fn ($f) => 'family=' . str_replace(' ', '+', $f) . ':wght@400;500;600;700')->implode('&');
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?{{ $families }}&display=swap" rel="stylesheet">

<style>
    :root {
        {!! $emit('leaf', $primary) !!}
        {!! $emit('clay', $accent) !!}
        --font-display: '{{ $heading }}', ui-serif, Georgia, serif;
        --font-sans: '{{ $body }}', ui-sans-serif, system-ui, sans-serif;
    }
</style>
