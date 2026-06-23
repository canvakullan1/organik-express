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

    // İkonlar (heroicon path'leri)
    $ic = [
        'cash' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'bag' => 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z',
        'trend' => 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941',
        'card' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z',
        'users' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        'eye' => 'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        'cart' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
        'checkout' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z',
    ];

    // Renkli degrade KPI kartları (ikas tarzı)
    $heroCards = [
        ['Ciro', $try($m['revenue']), 'linear-gradient(135deg,#10b981 0%,#047857 100%)', $num($m['purchases']) . ' sipariş', $cmp['revenue']['delta'], $ic['cash'], '#10b981'],
        ['Sipariş', $num($m['purchases']), 'linear-gradient(135deg,#6366f1 0%,#4338ca 100%)', 'tamamlanan satış', $cmp['purchases']['delta'], $ic['bag'], '#6366f1'],
        ['Dönüşüm Oranı', $m['conversion'] . '%', 'linear-gradient(135deg,#f59e0b 0%,#ea580c 100%)', 'oturum → satış', $cmp['conversion']['delta'], $ic['trend'], '#f59e0b'],
        ['Ort. Sepet', $try($m['avg_order']), 'linear-gradient(135deg,#a855f7 0%,#7e22ce 100%)', 'sipariş başına', $cmp['avg_order']['delta'], $ic['card'], '#a855f7'],
    ];

    $trendBadge = function (?float $d) {
        if ($d === null) return ['—', ''];
        $up = $d >= 0;
        return [($up ? '▲ ' : '▼ ') . ($up ? '+' : '') . number_format($d, 1, ',', '.') . '%', $up];
    };
@endphp

<x-filament-panels::page>
    {{-- Tarih filtresi + hızlı ön ayarlar --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            <div class="flex-1">{{ $this->form }}</div>
            <div class="flex flex-wrap gap-2">
                @foreach(['today' => 'Bugün', '7d' => 'Son 7 Gün', '30d' => 'Son 30 Gün', 'month' => 'Bu Ay', 'prev_month' => 'Geçen Ay'] as $k => $lbl)
                    <button type="button" wire:click="setPreset('{{ $k }}')"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-gray-600 ring-1 ring-gray-200 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/5">
                        {{ $lbl }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Renkli KPI kartları --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach($heroCards as [$label, $value, $grad, $sub, $delta, $iconPath, $hex])
            @php([$dTxt, $dUp] = $trendBadge($delta))
            <div class="relative overflow-hidden rounded-2xl p-5 text-white shadow-lg transition-transform duration-200 hover:-translate-y-0.5"
                 style="background:{{ $grad }}; box-shadow:0 10px 30px -10px {{ $hex }}80;">
                {{-- dekoratif daireler --}}
                <div class="pointer-events-none absolute -right-8 -top-10 h-32 w-32 rounded-full bg-white/10"></div>
                <div class="pointer-events-none absolute -right-2 bottom-2 h-16 w-16 rounded-full bg-white/5"></div>

                <div class="relative flex items-center justify-between">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-white/20 backdrop-blur">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/></svg>
                    </span>
                    @if($dTxt !== '—')
                        <span class="rounded-full bg-white/20 px-2.5 py-1 text-xs font-bold backdrop-blur">{{ $dTxt }}</span>
                    @endif
                </div>

                <p class="relative mt-4 text-sm font-medium text-white/80">{{ $label }}</p>
                <p class="relative mt-0.5 text-3xl font-extrabold tracking-tight tabular-nums">{{ $value }}</p>
                <p class="relative mt-1 text-xs text-white/70">{{ $sub }}</p>
            </div>
        @endforeach
    </div>

    {{-- İkincil metrikler (renkli ikon tile) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['Oturum', $num($m['sessions']), $ic['users'], '#3b82f6'],
            ['Ürün Görüntüleme', $num($m['product_views']), $ic['eye'], '#8b5cf6'],
            ['Sepete Ekleme', $num($m['add_to_cart']), $ic['cart'], '#f59e0b'],
            ['Ödemeye Ulaşma', $num($m['reached_checkout']), $ic['checkout'], '#10b981'],
        ] as [$label, $value, $iconPath, $hex])
            <div class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-3.5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl" style="background:{{ $hex }}1a; color:{{ $hex }};">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $value }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Günlük grafik: degrade alan (ciro) + çubuk (sepet) --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-4 mb-4">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Günlük Hareket</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Ciro (alan) ve sepete eklemeler (çubuk)</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-3 rounded-full" style="background:#6366f1;"></span>Sepet</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-3 rounded-full" style="background:#10b981;"></span>Ciro</span>
            </div>
        </div>

        @if(empty($daily))
            <p class="py-12 text-center text-sm text-gray-400">Bu aralıkta veri yok.</p>
        @else
            @php
                $n = count($daily);
                $W = max(640, $n * 26);
                $H = 240;
                $padL = 48; $padR = 14; $padT = 14; $padB = 26;
                $innerW = $W - $padL - $padR;
                $innerH = $H - $padT - $padB;
                $bandW = $innerW / max($n, 1);
                $barW = min(20, $bandW * 0.5);
                $x = fn ($i) => $padL + $bandW * ($i + 0.5);
                $yRev = fn ($v) => $padT + $innerH - ($v / $maxRev) * $innerH;
                $yBar = fn ($v) => ($v / $maxCarts) * $innerH;
                $linePts = [];
                foreach ($daily as $i => $d) { $linePts[] = round($x($i), 1) . ',' . round($yRev($d['revenue']), 1); }
                $linePath = implode(' ', $linePts);
                // Alan dolgusu için kapanış (en alta in)
                $first = $x(0); $last = $x($n - 1); $base = $padT + $innerH;
                $areaPath = 'M' . round($first, 1) . ',' . round($base, 1) . ' L' . $linePath . ' L' . round($last, 1) . ',' . round($base, 1) . ' Z';
                $labelEvery = max(1, (int) ceil($n / 8));
                $grid = 4;
            @endphp

            <div class="overflow-x-auto">
                <svg viewBox="0 0 {{ $W }} {{ $H }}" width="100%" preserveAspectRatio="xMidYMid meet" style="min-width: {{ $W }}px;" class="select-none">
                    <defs>
                        <linearGradient id="revArea" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#10b981" stop-opacity="0.35"/>
                            <stop offset="100%" stop-color="#10b981" stop-opacity="0"/>
                        </linearGradient>
                        <linearGradient id="barGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#818cf8"/>
                            <stop offset="100%" stop-color="#6366f1"/>
                        </linearGradient>
                    </defs>

                    {{-- gridline + ciro ekseni --}}
                    @for($g = 0; $g <= $grid; $g++)
                        @php $gy = $padT + $innerH - ($g / $grid) * $innerH; $gv = $maxRev * $g / $grid; @endphp
                        <line x1="{{ $padL }}" y1="{{ round($gy,1) }}" x2="{{ $W - $padR }}" y2="{{ round($gy,1) }}"
                              stroke="currentColor" class="text-gray-200 dark:text-white/10" stroke-width="1" stroke-dasharray="{{ $g === 0 ? '0' : '3 3' }}"/>
                        <text x="{{ $padL - 8 }}" y="{{ round($gy + 3,1) }}" text-anchor="end" font-size="10"
                              fill="currentColor" class="text-gray-400 dark:text-gray-500">{{ $tryShort($gv) }}</text>
                    @endfor

                    {{-- çubuklar (sepet) --}}
                    @foreach($daily as $i => $d)
                        @php $bh = $yBar($d['carts']); $bx = $x($i) - $barW / 2; $by = $padT + $innerH - $bh; @endphp
                        @if($d['carts'] > 0)
                            <rect x="{{ round($bx,1) }}" y="{{ round($by,1) }}" width="{{ round($barW,1) }}" height="{{ round($bh,1) }}" rx="3" fill="url(#barGrad)" opacity="0.55"/>
                        @endif
                    @endforeach

                    {{-- ciro alan dolgusu + çizgi --}}
                    <path d="{{ $areaPath }}" fill="url(#revArea)"/>
                    <polyline points="{{ $linePath }}" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    @foreach($daily as $i => $d)
                        <circle cx="{{ round($x($i),1) }}" cy="{{ round($yRev($d['revenue']),1) }}" r="3" fill="#10b981" stroke="#fff" stroke-width="1.5"/>
                    @endforeach

                    {{-- x etiketleri + hover --}}
                    @foreach($daily as $i => $d)
                        @if($i % $labelEvery === 0)
                            <text x="{{ round($x($i),1) }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="10" fill="currentColor" class="text-gray-400 dark:text-gray-500">{{ $d['date'] }}</text>
                        @endif
                        <rect x="{{ round($x($i) - $bandW/2,1) }}" y="{{ $padT }}" width="{{ round($bandW,1) }}" height="{{ $innerH }}" fill="transparent">
                            <title>{{ $d['date'] }} · {{ $num($d['carts']) }} sepet · {{ $try($d['revenue']) }}</title>
                        </rect>
                    @endforeach
                </svg>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Dönüşüm hunisi (renkli) --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Dönüşüm Hunisi</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Adımlar arası geçiş ve kayıp</p>
            @php $funnelColors = ['#6366f1', '#8b5cf6', '#ec4899', '#10b981']; @endphp
            <div class="space-y-4">
                @foreach($funnel as $i => $step)
                    @php $fc = $funnelColors[$i % count($funnelColors)]; @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="flex items-center gap-2 font-medium text-gray-700 dark:text-gray-300">
                                <span class="grid h-6 w-6 place-items-center rounded-full text-[11px] font-bold text-white" style="background:{{ $fc }};">{{ $i + 1 }}</span>
                                {{ $step['label'] }}
                            </span>
                            <span class="tabular-nums text-gray-500 dark:text-gray-400">
                                <strong class="text-gray-900 dark:text-white">{{ $num($step['count']) }}</strong>
                                @if($step['step_conv'] !== null)<span class="ml-1 text-xs">· {{ $step['step_conv'] }}%</span>@endif
                            </span>
                        </div>
                        <div class="h-3.5 rounded-full bg-gray-100 dark:bg-white/5 overflow-hidden">
                            <div class="h-full rounded-full transition-all" style="width: {{ max(2, $step['pct']) }}%; background:linear-gradient(90deg,{{ $fc }}99,{{ $fc }});"></div>
                        </div>
                        @if($step['drop'] !== null && $step['drop'] > 0)
                            <p class="mt-1 text-[11px]" style="color:#ef4444;">↓ %{{ $step['drop'] }} bu adımda kayıp</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Trafik kaynakları --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Trafik Kaynakları</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Siparişin geldiği kanal</p>
            @if(empty($channels))
                <p class="py-8 text-center text-sm text-gray-400">Bu aralıkta veri yok.</p>
            @else
                @php $maxChRev = max(collect($channels)->max('revenue') ?: 1, 1); $chColors = ['#10b981','#6366f1','#f59e0b','#ec4899','#06b6d4','#8b5cf6']; @endphp
                <div class="space-y-3.5">
                    @foreach($channels as $i => $c)
                        @php $cc = $chColors[$i % count($chColors)]; @endphp
                        <div class="flex items-center gap-3">
                            <div class="w-28 shrink-0">
                                <p class="truncate text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $c['channel'] }}</p>
                                <p class="text-[11px] text-gray-400">{{ $num($c['sessions']) }} oturum · {{ $num($c['orders']) }} sipariş</p>
                            </div>
                            <div class="flex-1">
                                <div class="h-3 rounded-full bg-gray-100 dark:bg-white/5 overflow-hidden">
                                    <div class="h-full rounded-full" style="width: {{ max(3, $c['revenue'] / $maxChRev * 100) }}%; background:linear-gradient(90deg,{{ $cc }}aa,{{ $cc }});"></div>
                                </div>
                            </div>
                            <span class="w-24 shrink-0 text-right text-sm font-bold tabular-nums text-gray-900 dark:text-white">{{ $try($c['revenue']) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Kaynak kırılımı --}}
    @if(! empty($sources))
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Kaynak Kırılımı</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">utm_source / yönlendiren site (ilk 10)</p>
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
                                <td class="py-2.5 font-semibold text-gray-800 dark:text-gray-200">{{ $s['source'] }}</td>
                                <td class="py-2.5"><span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium" style="background:#6366f11a; color:#6366f1;">{{ $s['channel'] }}</span></td>
                                <td class="py-2.5 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $num($s['sessions']) }}</td>
                                <td class="py-2.5 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $num($s['carts']) }}</td>
                                <td class="py-2.5 text-right font-bold tabular-nums text-gray-900 dark:text-white">{{ $try($s['revenue']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament-panels::page>
