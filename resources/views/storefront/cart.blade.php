@extends('layouts.storefront')

@section('title', 'Sepetim — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@section('content')
@php
    $threshold = app(\App\Settings\GeneralSettings::class)->free_shipping_threshold ?: 750;
    $remaining = max(0, $threshold - $subtotal);
    $progress = min(100, $subtotal / $threshold * 100);
@endphp

<div class="mx-auto max-w-6xl px-4 py-10">
    <h1 class="font-display text-3xl font-600 text-bark mb-8">Sepetim</h1>

    @if($lines->isEmpty())
        <div class="rounded-3xl border border-dashed border-leaf-200 bg-white p-16 text-center">
            <span class="grid size-16 place-items-center rounded-full bg-leaf-50 text-leaf-400 mx-auto mb-4">
                <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke-width="1.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272"/></svg>
            </span>
            <p class="font-display text-xl text-bark">Sepetiniz boş</p>
            <p class="mt-2 text-sm text-bark/60">Taze ve organik ürünleri keşfetmeye başlayın.</p>
            <a href="{{ route('home') }}" class="btn-leaf mt-6">Alışverişe başla</a>
        </div>
    @else
        <div class="grid lg:grid-cols-3 gap-8">
            {{-- Satırlar --}}
            <div class="lg:col-span-2 space-y-4">
                {{-- Kargo eşiği çubuğu --}}
                <div class="rounded-2xl border border-leaf-100 bg-leaf-50/50 p-4">
                    @if($remaining > 0)
                        <p class="text-sm text-bark/70">Kargo bedava için <strong class="text-leaf-700 tnum">₺{{ number_format($remaining, 2, ',', '.') }}</strong> daha ekleyin.</p>
                    @else
                        <p class="text-sm text-leaf-700 font-600 flex items-center gap-1.5">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            Tebrikler! Kargonuz bedava.
                        </p>
                    @endif
                    <div class="mt-2 h-2 rounded-full bg-leaf-100 overflow-hidden">
                        <div class="h-full rounded-full bg-leaf-500 transition-all" style="width: {{ $progress }}%"></div>
                    </div>
                </div>

                @foreach($lines as $item)
                    <div class="flex gap-4 rounded-2xl border border-paper bg-white p-4">
                        <a href="{{ $item['url'] }}" class="size-24 shrink-0 rounded-xl overflow-hidden bg-paper">
                            @if($item['cover'])<img src="{{ $item['cover'] }}" alt="{{ $item['name'] }}" class="size-full object-cover">@endif
                        </a>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <a href="{{ $item['url'] }}" class="font-600 text-bark hover:text-leaf-700 line-clamp-1">{{ $item['name'] }}</a>
                                    <p class="text-xs text-bark/50 mt-0.5">@if($item['type'] === 'bundle')<span class="text-leaf-700 font-600">Hazır Kutu</span> · @endif{{ $item['sub'] }}</p>
                                </div>
                                <form action="{{ route('cart.remove') }}" method="POST">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="type" value="{{ $item['type'] }}">
                                    <input type="hidden" name="variant_id" value="{{ $item['id'] }}">
                                    <button class="text-bark/30 hover:text-clay-500 p-1" aria-label="Çıkar">
                                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </form>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-2">
                                <form action="{{ route('cart.update') }}" method="POST" class="flex items-center rounded-full border border-leaf-200" x-data>
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="type" value="{{ $item['type'] }}">
                                    <input type="hidden" name="variant_id" value="{{ $item['id'] }}">
                                    <button type="submit" name="qty" value="{{ max(1, (int) $item['qty'] - 1) }}" class="grid size-9 place-items-center text-bark hover:text-leaf-700" aria-label="Azalt">
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                                    </button>
                                    <span class="w-9 text-center text-sm font-600 tnum">{{ (int) $item['qty'] }}</span>
                                    <button type="submit" name="qty" value="{{ (int) $item['qty'] + 1 }}" class="grid size-9 place-items-center text-bark hover:text-leaf-700" aria-label="Artır">
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                                    </button>
                                </form>
                                <span class="font-display font-600 text-leaf-800 tnum">₺{{ number_format($item['line_total'], 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Özet --}}
            <div class="lg:col-span-1">
                <div class="rounded-2xl border border-paper bg-white p-6 sticky top-28">
                    <h2 class="font-display text-xl font-600 text-bark mb-4">Sipariş Özeti</h2>

                    {{-- Kupon --}}
                    <div class="mb-4">
                        @if($couponCode)
                            <div class="flex items-center justify-between rounded-lg bg-leaf-50 border border-leaf-200 px-3 py-2">
                                <span class="text-sm font-600 text-leaf-800">Kupon: {{ $couponCode }}</span>
                                <form action="{{ route('cart.coupon.remove') }}" method="POST">@csrf @method('DELETE')
                                    <button class="text-xs text-clay-600 hover:underline">Kaldır</button>
                                </form>
                            </div>
                        @else
                            <form action="{{ route('cart.coupon.apply') }}" method="POST" class="flex gap-2">
                                @csrf
                                <input name="code" placeholder="Kupon kodu" class="flex-1 rounded-lg border border-paper bg-cream/50 px-3 py-2 text-sm uppercase focus:border-leaf-400 focus:outline-none">
                                <button class="rounded-full bg-leaf-600 px-4 text-sm font-600 text-white hover:bg-leaf-700">Uygula</button>
                            </form>
                            @error('coupon')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between"><span class="text-bark/60">Ara Toplam</span><span class="font-600 tnum">₺{{ number_format($subtotal, 2, ',', '.') }}</span></div>
                        @if($couponDiscount > 0)
                            <div class="flex justify-between text-leaf-700"><span>Kupon İndirimi</span><span class="font-600 tnum">-₺{{ number_format($couponDiscount, 2, ',', '.') }}</span></div>
                        @endif
                        <div class="flex justify-between"><span class="text-bark/60">Kargo</span><span class="font-600 {{ $remaining > 0 ? '' : 'text-leaf-600' }}">{{ $remaining > 0 ? 'Adımda hesaplanır' : 'Bedava' }}</span></div>
                    </div>
                    <div class="my-4 border-t border-paper"></div>
                    <div class="flex justify-between items-baseline">
                        <span class="font-600 text-bark">Toplam</span>
                        <span class="font-display text-2xl font-700 text-leaf-800 tnum">₺{{ number_format(max(0, $subtotal - $couponDiscount), 2, ',', '.') }}</span>
                    </div>
                    <p class="text-xs text-bark/40 mt-1">KDV dahil · puan kullanımı ödeme adımında</p>

                    <a href="{{ route('cart.checkout') }}" class="btn-leaf w-full mt-5">
                        Ödemeye Geç
                    </a>
                    <p class="text-center text-xs text-bark/40 mt-2">Güvenli ödeme · 3D Secure</p>
                    <a href="{{ route('home') }}" class="block text-center text-sm text-leaf-700 hover:underline mt-4">← Alışverişe devam et</a>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
