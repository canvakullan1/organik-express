@php
    $m = $this->getMetrics();
    $cmp = $this->getComparison();
    $funnel = $this->getFunnel();
    $channels = $this->getChannels();
    $sources = $this->getSources();
    $daily = $this->getDailySeries();
    $maxCarts = max(collect($daily)->max('carts') ?: 1, 1);
    $maxRev = max(collect($daily)->max('revenue') ?: 1, 1);
    $try = fn ($v) => '₺' . number_format((float) $v, 2, ',', '.');
    $tryShort = function ($v) {
        $v = (float) $v;
        if ($v >= 1000000) return '₺' . rtrim(rtrim(number_format($v / 1000000, 1, ',', '.'), '0'), ',') . 'M';
        if ($v >= 1000) return '₺' . rtrim(rtrim(number_format($v / 1000, 1, ',', '.'), '0'), ',') . 'B';
        return '₺' . number_format($v, 0, ',', '.');
    };
    $num = fn ($v) => number_format((int) $v, 0, ',', '.');

    // Trend rozet yardımcısı: [etiket, metin rengi, arka plan, ikon yolu]
    $trend = function (?float $d) {
        if ($d === null) return ['—', 'text-gray-400 dark:text-gray-500', 'bg-gray-100 dark:bg-white/5', null];
        $up = $d >= 0;
        return [
            ($up ? '+' : '') . number_format($d, 1, ',', '.') . '%',
            $up ? 'text-success-700 dark:text-success-400' : 'text-danger-700 dark:text-danger-400',
            $up ? 'bg-success-50 dark:bg-success-500/10' : 'bg-danger-50 dark:bg-danger-500/10',
            $up ? 'M4.5 15.75l7.5-7.5 7.5 7.5' : 'M19.5 8.25l-7.5 7.5-7.5-7.5',
        ];
    };

    // Renk tonu sınıfları (blob arka planı + ikon kutusu) — @switch tuzağından kaçınmak için önceden hesaplanır
    $tone = [
        'success' => ['bg-success-500', 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400'],
        'primary' => ['bg-primary-500', 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400'],
        'warning' => ['bg-warning-500', 'bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400'],
        'info' => ['bg-sky-500', 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400'],
    ];

    $heroCards = [
        ['Ciro', $try($m['revenue']), 'heroicon-o-banknotes', 'success', $num($m['purchases']) . ' sipariş', $cmp['revenue']['delta']],
        ['Sipariş', $num($m['purchases']), 'heroicon-o-shopping-bag', 'primary', 'tamamlanan satış', $cmp['purchases']['delta']],
        ['Dönüşüm Oranı', $m['conversion'] . '%', 'heroicon-o-arrow-trending-up', 'warning', 'oturum → satış', $cmp['conversion']['delta']],
        ['Ort. Sepet', $try($m['avg_order']), 'heroicon-o-calculator', 'info', 'sipariş başına', $cmp['avg_order']['delta']],
    ];
@endphp

<x-filament-panels::page>
    {{-- Tarih filtresi + ön ayarlar --}}
    <x-filament::section>
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            <div class="flex-1">{{ $this->form }}</div>
            <div class="flex flex-wrap gap-2">
                <x-filament::button color="gray" size="sm" wire:click="setPreset('today')">Bugün</x-filament::button>
                <x-filament::button color="gray" size="sm" wire:click="setPreset('7d')">Son 7 Gün</x-filament::button>
                <x-filament::button color="gray" size="sm" wire:click="setPreset('30d')">Son 30 Gün</x-filament::button>
                <x-filament::button color="gray" size="sm" wire:click="setPreset('month')">Bu Ay</x-filament::button>
                <x-filament::button color="gray" size="sm" wire:click="setPreset('prev_month')">Geçen Ay</x-filament::button>
            </div>
        </div>
    </x-filament::section>

    {{-- KPI kartları (önceki döneme göre trend) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach($heroCards as [$label, $value, $icon, $color, $sub, $delta])
            @php
                [$dLabel, $dText, $dBg, $dIcon] = $trend($delta);
                [$blobBg, $tileCls] = $tone[$color] ?? $tone['info'];
            @endphp
            <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-gray-900">
                <div class="pointer-events-none absolute -right-6 -top-8 h-28 w-28 rounded-full opacity-[0.07] {{ $blobBg }}"></div>

                <div class="relative flex items-center justify-between">
                    <span class="grid h-10 w-10 place-items-center rounded-xl {{ $tileCls }}">
                        <x-filament::icon :icon="$icon" class="h-5 w-5" />
                    </span>

                    <span class="inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-xs font-semibold {{ $dText }} {{ $dBg }}">
                        @if($dIcon)<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $dIcon }}"/></svg>@endif
                        {{ $dLabel }}
                    </span>
                </div>

                <p class="relative mt-4 text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="relative mt-0.5 text-3xl font-bold tracking-tight text-gray-950 tabular-nums dark:text-white">{{ $value }}</p>
                <p class="relative mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $sub }}</p>
            </div>
        @endforeach
    </div>

    {{-- İkincil metrikler --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['Oturum', $num($m['sessions']), 'heroicon-m-users'],
            ['Ürün Görüntüleme', $num($m['product_views']), 'heroicon-m-eye'],
            ['Sepete Ekleme', $num($m['add_to_cart']), 'heroicon-m-shopping-cart'],
            ['Ödemeye Ulaşma', $num($m['reached_checkout']), 'heroicon-m-credit-card'],
        ] as [$label, $value, $icon])
            <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/60 px-4 py-3 dark:border-white/10 dark:bg-white/5">
                <x-filament::icon :icon="$icon" class="h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500" />
                <div class="min-w-0">
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="text-lg font-semibold tabular-nums text-gray-950 dark:text-white">{{ $value }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Günlük grafik: çubuk (sepet) + çizgi (ciro) --}}
    <x-filament::section>
        <x-slot name="heading">Günlük Hareket</x-slot>
        <x-slot name="description">Sepete eklemeler (çubuk) ve ciro (çizgi)</x-slot>

        <div class="flex items-center gap-5 text-xs font-medium text-gray-500 dark:text-gray-400 mb-3">
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-primary-500/80"></span>Sepete Ekleme</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-3 rounded-full" style="background:#f59e0b;"></span>Ciro</span>
        </div>

        @if(empty($daily))
            <p class="py-12 text-center text-sm text-gray-400">Bu aralıkta veri yok.</p>
        @else
            @php
                $n = count($daily);
                $W = max(640, $n * 26);
                $H = 230;
                $padL = 48; $padR = 14; $padT = 14; $padB = 26;
                $innerW = $W - $padL - $padR;
                $innerH = $H - $padT - $padB;
                $bandW = $innerW / max($n, 1);
                $barW = min(22, $bandW * 0.55);
                $x = fn ($i) => $padL + $bandW * ($i + 0.5);
                $yRev = fn ($v) => $padT + $innerH - ($v / $maxRev) * $innerH;
                $yBar = fn ($v) => ($v / $maxCarts) * $innerH;
                // Ciro çizgisi noktaları
                $linePts = [];
                foreach ($daily as $i => $d) { $linePts[] = round($x($i), 1) . ',' . round($yRev($d['revenue']), 1); }
                $linePath = implode(' ', $linePts);
                // X ekseni etiket aralığı (~8 etiket)
                $labelEvery = max(1, (int) ceil($n / 8));
                // Y ekseni gridline değerleri (ciro)
                $grid = 4;
            @endphp

            <div class="overflow-x-auto">
                <svg viewBox="0 0 {{ $W }} {{ $H }}" width="100%" preserveAspectRatio="xMidYMid meet" style="min-width: {{ $W }}px;" class="select-none">
                    {{-- Yatay gridline + ciro ekseni --}}
                    @for($g = 0; $g <= $grid; $g++)
                        @php $gy = $padT + $innerH - ($g / $grid) * $innerH; $gv = $maxRev * $g / $grid; @endphp
                        <line x1="{{ $padL }}" y1="{{ round($gy,1) }}" x2="{{ $W - $padR }}" y2="{{ round($gy,1) }}"
                              stroke="currentColor" class="text-gray-200 dark:text-white/10" stroke-width="1" stroke-dasharray="{{ $g === 0 ? '0' : '3 3' }}"/>
                        <text x="{{ $padL - 8 }}" y="{{ round($gy + 3,1) }}" text-anchor="end" font-size="10"
                              fill="currentColor" class="text-gray-400 dark:text-gray-500 tabular-nums">{{ $tryShort($gv) }}</text>
                    @endfor

                    {{-- Çubuklar (sepete ekleme) --}}
                    @foreach($daily as $i => $d)
                        @php $bh = $yBar($d['carts']); $bx = $x($i) - $barW / 2; $by = $padT + $innerH - $bh; @endphp
                        @if($d['carts'] > 0)
                            <rect x="{{ round($bx,1) }}" y="{{ round($by,1) }}" width="{{ round($barW,1) }}" height="{{ round($bh,1) }}"
                                  rx="3" class="fill-primary-500/30 dark:fill-primary-500/25"/>
                        @endif
                    @endforeach

                    {{-- Ciro çizgisi --}}
                    <polyline points="{{ $linePath }}" fill="none" stroke="#f59e0b" stroke-width="2.5"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    @foreach($daily as $i => $d)
                        <circle cx="{{ round($x($i),1) }}" cy="{{ round($yRev($d['revenue']),1) }}" r="3" fill="#f59e0b" stroke="#fff" stroke-width="1.5"/>
                    @endforeach

                    {{-- X ekseni etiketleri + hover alanları (native tooltip) --}}
                    @foreach($daily as $i => $d)
                        @if($i % $labelEvery === 0)
                            <text x="{{ round($x($i),1) }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="10"
                                  fill="currentColor" class="text-gray-400 dark:text-gray-500">{{ $d['date'] }}</text>
                        @endif
                        <rect x="{{ round($x($i) - $bandW/2,1) }}" y="{{ $padT }}" width="{{ round($bandW,1) }}" height="{{ $innerH }}" fill="transparent">
                            <title>{{ $d['date'] }} · {{ $num($d['carts']) }} sepet · {{ $try($d['revenue']) }}</title>
                        </rect>
                    @endforeach
                </svg>
            </div>
        @endif
    </x-filament::section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Dönüşüm hunisi --}}
        <x-filament::section>
            <x-slot name="heading">Dönüşüm Hunisi</x-slot>
            <x-slot name="description">Adımlar arası geçiş ve kayıp oranları</x-slot>
            <div class="space-y-4">
                @foreach($funnel as $i => $step)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="flex items-center gap-2 font-medium text-gray-700 dark:text-gray-300">
                                <span class="grid h-5 w-5 place-items-center rounded-full bg-gray-100 text-[11px] font-bold text-gray-500 dark:bg-white/10 dark:text-gray-400">{{ $i + 1 }}</span>
                                {{ $step['label'] }}
                            </span>
                            <span class="tabular-nums text-gray-500 dark:text-gray-400">
                                <strong class="text-gray-900 dark:text-white">{{ $num($step['count']) }}</strong>
                                @if($step['step_conv'] !== null)
                                    <span class="ml-1 text-xs">· {{ $step['step_conv'] }}% geçiş</span>
                                @endif
                            </span>
                        </div>
                        <div class="h-3.5 rounded-full bg-gray-100 dark:bg-white/5 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-primary-400 to-primary-600 transition-all" style="width: {{ max(2, $step['pct']) }}%"></div>
                        </div>
                        @if($step['drop'] !== null && $step['drop'] > 0)
                            <p class="mt-1 text-[11px] text-danger-500 dark:text-danger-400">↓ %{{ $step['drop'] }} bu adımda kayıp</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Trafik kaynakları (kanal) --}}
        <x-filament::section>
            <x-slot name="heading">Trafik Kaynakları</x-slot>
            <x-slot name="description">Siparişin nereden geldiği (kanal bazlı)</x-slot>
            @if(empty($channels))
                <p class="py-8 text-center text-sm text-gray-400">Bu aralıkta veri yok.</p>
            @else
                @php $maxChRev = max(collect($channels)->max('revenue') ?: 1, 1); @endphp
                <div class="space-y-3">
                    @foreach($channels as $c)
                        <div class="flex items-center gap-3">
                            <div class="w-28 shrink-0">
                                <p class="truncate text-sm font-medium text-gray-700 dark:text-gray-300">{{ $c['channel'] }}</p>
                                <p class="text-[11px] text-gray-400">{{ $num($c['sessions']) }} oturum · {{ $num($c['orders']) }} sipariş</p>
                            </div>
                            <div class="flex-1">
                                <div class="h-2.5 rounded-full bg-gray-100 dark:bg-white/5 overflow-hidden">
                                    <div class="h-full rounded-full bg-success-500" style="width: {{ max(2, $c['revenue'] / $maxChRev * 100) }}%"></div>
                                </div>
                            </div>
                            <span class="w-24 shrink-0 text-right text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $try($c['revenue']) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- Detaylı kaynaklar --}}
    @if(! empty($sources))
        <x-filament::section>
            <x-slot name="heading">Kaynak Kırılımı</x-slot>
            <x-slot name="description">utm_source / yönlendiren site bazlı (ilk 10)</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-white/10">
                            <th class="pb-2.5 font-semibold">Kaynak</th>
                            <th class="pb-2.5 font-semibold">Kanal</th>
                            <th class="pb-2.5 font-semibold text-right">Oturum</th>
                            <th class="pb-2.5 font-semibold text-right">Sepet</th>
                            <th class="pb-2.5 font-semibold text-right">Ciro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($sources as $s)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="py-2.5 font-medium text-gray-800 dark:text-gray-200">{{ $s['source'] }}</td>
                                <td class="py-2.5"><span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ $s['channel'] }}</span></td>
                                <td class="py-2.5 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $num($s['sessions']) }}</td>
                                <td class="py-2.5 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $num($s['carts']) }}</td>
                                <td class="py-2.5 text-right font-semibold tabular-nums text-gray-900 dark:text-white">{{ $try($s['revenue']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
