@extends('layouts.storefront')

@section('title', 'Siparişlerim — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@php($try = fn ($v) => '₺' . number_format((float) $v, 2, ',', '.'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
    <a href="{{ route('account.index') }}" class="text-sm text-bark/50 hover:text-leaf-700">← Hesabım</a>
    <h1 class="font-display text-2xl font-700 text-bark mt-1 mb-6">Siparişlerim</h1>

    @if($orders->isEmpty())
        <div class="rounded-2xl border border-dashed border-leaf-200 bg-white p-10 text-center">
            <p class="text-bark/60">Henüz siparişin yok.</p>
            <a href="{{ route('home') }}" class="btn-leaf !rounded-lg mt-4">Alışverişe başla</a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($orders as $order)
                <a href="{{ route('account.order.show', $order) }}" class="flex items-center justify-between gap-4 rounded-2xl border border-paper bg-white p-5 hover:border-leaf-200 hover:shadow-sm transition">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-700 text-bark">{{ $order->order_number }}</span>
                            <span class="chip" style="background-color: var(--color-leaf-50); color: var(--color-leaf-700)">{{ $order->status->getLabel() }}</span>
                        </div>
                        <p class="text-sm text-bark/50 mt-1">{{ $order->created_at->translatedFormat('d F Y, H:i') }} · {{ $order->items_count }} ürün</p>
                    </div>
                    <span class="font-700 text-leaf-700 tnum">{{ $try($order->grand_total) }}</span>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
