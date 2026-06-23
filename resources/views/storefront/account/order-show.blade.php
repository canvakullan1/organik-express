@extends('layouts.storefront')

@section('title', $order->order_number . ' — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@php($try = fn ($v) => '₺' . number_format((float) $v, 2, ',', '.'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
    <a href="{{ route('account.orders') }}" class="text-sm text-bark/50 hover:text-leaf-700">← Siparişlerim</a>
    <div class="flex flex-wrap items-center gap-3 mt-1 mb-6">
        <h1 class="font-display text-2xl font-700 text-bark">{{ $order->order_number }}</h1>
        <span class="chip" style="background-color: var(--color-leaf-50); color: var(--color-leaf-700)">{{ $order->status->getLabel() }}</span>
        <span class="chip bg-paper text-bark/70">{{ $order->payment_status->getLabel() }}</span>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 mb-4">
        {{-- Kargo --}}
        <div class="rounded-2xl border border-paper bg-white p-5 text-sm">
            <h2 class="font-700 text-bark mb-2">Teslimat</h2>
            <p class="text-bark/80">{{ $order->shipping_address['name'] ?? '' }} · {{ $order->shipping_address['phone'] ?? '' }}</p>
            <p class="text-bark/60 mt-1">{{ $order->shipping_address['address'] ?? '' }}, {{ $order->shipping_address['district'] ?? '' }}/{{ $order->shipping_address['city'] ?? '' }}</p>
            @if($order->delivery_date)<p class="mt-2 text-bark/70"><strong>Teslimat:</strong> {{ \Illuminate\Support\Carbon::parse($order->delivery_date)->translatedFormat('d F Y') }} {{ $order->delivery_slot }}</p>@endif
            @if($order->shipment?->tracking_number)
                <p class="mt-2 text-leaf-700"><strong>Kargo:</strong> {{ $order->shipment->carrier }} · {{ $order->shipment->tracking_number }}
                    @if($order->shipment->tracking_url)
                        <a href="{{ $order->shipment->tracking_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-600 hover:underline">Takip et
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                        </a>
                    @endif
                </p>
            @endif
        </div>
        {{-- Ödeme --}}
        <div class="rounded-2xl border border-paper bg-white p-5 text-sm">
            <h2 class="font-700 text-bark mb-2">Ödeme</h2>
            <p class="text-bark/80">{{ $order->payment_method?->getLabel() }}</p>
            @if($order->payment_method?->value === 'bank_transfer' && ! $order->isPaid())
                @php($c = app(\App\Settings\PaymentSettings::class))
                <div class="mt-2 text-bark/70">
                    <p>{{ $c->bank_name }} · IBAN: {{ $c->bank_iban }}</p>
                    <p class="text-xs text-clay-700 mt-1">Açıklama: {{ $order->order_number }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Kalemler --}}
    <div class="rounded-2xl border border-paper bg-white p-5">
        @foreach($order->items as $item)
            <div class="flex justify-between text-sm py-2 border-b border-paper/60 last:border-0">
                <span class="text-bark/80">{{ $item->name }} @if($item->variant_name)<span class="text-bark/40">({{ $item->variant_name }})</span>@endif <span class="text-bark/40">× {{ rtrim(rtrim(number_format($item->quantity,3,',','.'),'0'),',') }}</span></span>
                <span class="tnum">{{ $try($item->line_total) }}</span>
            </div>
        @endforeach
        <div class="mt-3 pt-3 border-t border-paper space-y-1.5 text-sm">
            <div class="flex justify-between"><span class="text-bark/60">Ara Toplam</span><span class="tnum">{{ $try($order->subtotal) }}</span></div>
            @if($order->coupon_discount > 0)
                <div class="flex justify-between text-leaf-700"><span>Kupon ({{ $order->coupon_code }})</span><span class="tnum">-{{ $try($order->coupon_discount) }}</span></div>
            @endif
            @if($order->loyalty_used > 0)
                <div class="flex justify-between text-leaf-700"><span>Para Puan</span><span class="tnum">-{{ $try($order->loyalty_used) }}</span></div>
            @endif
            <div class="flex justify-between"><span class="text-bark/60">Kargo</span><span class="tnum">{{ $order->shipping_cost > 0 ? $try($order->shipping_cost) : 'Ücretsiz' }}</span></div>
            <div class="flex justify-between pt-1.5 border-t border-paper font-700"><span>Toplam</span><span class="text-leaf-700 tnum">{{ $try($order->grand_total) }}</span></div>
            @if($order->loyalty_earned > 0)
                <p class="text-xs text-clay-600 pt-1">Bu siparişten {{ number_format($order->loyalty_earned, 0, ',', '.') }} puan kazandınız.</p>
            @endif
        </div>
    </div>

    {{-- Durum geçmişi --}}
    @if($order->statusHistory->isNotEmpty())
        <div class="rounded-2xl border border-paper bg-white p-5 mt-4">
            <h2 class="font-700 text-bark mb-3">Sipariş Takibi</h2>
            <div class="space-y-3">
                @foreach($order->statusHistory as $h)
                    <div class="flex gap-3 text-sm">
                        <span class="mt-1 size-2 rounded-full bg-leaf-500 shrink-0"></span>
                        <div>
                            <p class="font-600 text-bark">{{ \App\Enums\OrderStatus::tryFrom($h->status)?->getLabel() ?? $h->status }}</p>
                            @if($h->note)<p class="text-bark/55">{{ $h->note }}</p>@endif
                            <p class="text-xs text-bark/40">{{ $h->created_at?->translatedFormat('d F Y, H:i') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
