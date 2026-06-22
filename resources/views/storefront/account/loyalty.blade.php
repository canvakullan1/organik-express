@extends('layouts.storefront')

@section('title', 'Para Puanım — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@php($pt = fn ($v) => number_format((float) $v, 2, ',', '.'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
    <a href="{{ route('account.index') }}" class="text-sm text-bark/50 hover:text-leaf-700">← Hesabım</a>
    <h1 class="font-display text-2xl font-700 text-bark mt-1 mb-6">Para Puanım</h1>

    {{-- Bakiye --}}
    <div class="rounded-2xl bg-leaf-700 text-white p-6 mb-6">
        <p class="text-leaf-100/80 text-sm">Güncel Bakiye</p>
        <p class="font-display text-4xl font-700 mt-1 tnum">{{ $pt($balance) }} <span class="text-2xl">puan</span></p>
        <p class="text-leaf-100/70 text-sm mt-2">1 puan = ₺1 · Bir sonraki siparişinde kullanabilirsin.</p>
    </div>

    @if($transactions->isEmpty())
        <div class="rounded-2xl border border-dashed border-leaf-200 bg-white p-10 text-center">
            <p class="text-bark/60">Henüz puan hareketin yok. Alışveriş yaptıkça puan kazanırsın!</p>
        </div>
    @else
        <div class="rounded-2xl border border-paper bg-white divide-y divide-paper">
            @foreach($transactions as $t)
                <div class="flex items-center justify-between p-4">
                    <div>
                        <p class="font-600 text-bark">{{ $t->description }}</p>
                        <p class="text-xs text-bark/40">{{ $t->created_at?->translatedFormat('d F Y, H:i') }}</p>
                    </div>
                    <span class="font-700 tnum {{ $t->points >= 0 ? 'text-leaf-700' : 'text-clay-600' }}">
                        {{ $t->points >= 0 ? '+' : '' }}{{ $pt($t->points) }}
                    </span>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
