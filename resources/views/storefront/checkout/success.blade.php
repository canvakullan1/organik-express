@extends('layouts.storefront')

@section('title', 'Siparişiniz Alındı — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@php($try = fn ($v) => '₺' . number_format((float) $v, 2, ',', '.'))

@section('content')
<div class="mx-auto max-w-2xl px-4 py-12">
    <div class="rounded-2xl border border-paper bg-white p-8 text-center">
        <span class="grid size-16 place-items-center rounded-full bg-leaf-100 text-leaf-700 mx-auto mb-4">
            <svg class="size-9" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
        </span>
        <h1 class="font-display text-2xl font-700 text-bark">Siparişiniz Alındı</h1>
        <p class="mt-2 text-bark/60">Sipariş No: <strong class="text-bark">{{ $order->order_number }}</strong></p>
        <p class="mt-1"><span class="chip" style="background:#0001">{{ $order->status->getLabel() }}</span></p>

        @if($order->payment_method?->value === 'bank_transfer')
            @php($c = app(\App\Settings\CheckoutSettings::class))
            <div class="mt-5 rounded-xl bg-clay-50 border border-clay-200 p-4 text-left text-sm">
                <p class="font-700 text-clay-800 mb-1">Havale/EFT ile ödeme bekleniyor</p>
                <p class="text-clay-800/80">{{ $c->bank_name }} · {{ $c->bank_account_holder }}</p>
                <p class="text-clay-800 font-600">IBAN: {{ $c->bank_iban }}</p>
                <p class="text-clay-800/70 mt-1">Açıklama kısmına <strong>{{ $order->order_number }}</strong> yazın.</p>
            </div>
        @endif
    </div>

    <div class="rounded-2xl border border-paper bg-white p-6 mt-4">
        <h2 class="font-700 text-bark mb-3">Özet</h2>
        @foreach($order->items as $item)
            <div class="flex justify-between text-sm py-1.5 border-b border-paper/60 last:border-0">
                <span class="text-bark/80">{{ $item->name }} <span class="text-bark/40">× {{ rtrim(rtrim(number_format($item->quantity,3,',','.'),'0'),',') }}</span></span>
                <span class="tnum">{{ $try($item->line_total) }}</span>
            </div>
        @endforeach
        <div class="flex justify-between mt-3 pt-3 border-t border-paper font-700">
            <span>Toplam</span><span class="text-leaf-700 tnum">{{ $try($order->grand_total) }}</span>
        </div>
    </div>

    <div class="flex gap-3 mt-5">
        <a href="{{ route('account.order.show', $order) }}" class="btn-leaf flex-1 !rounded-lg">Sipariş Detayı</a>
        <a href="{{ route('home') }}" class="btn-ghost flex-1 !rounded-lg">Alışverişe Devam</a>
    </div>
</div>
@endsection
