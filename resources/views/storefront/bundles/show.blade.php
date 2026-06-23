@extends('layouts.storefront')

@section('title', ($bundle->meta_title ?: $bundle->name) . ' — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@section('meta_description', $bundle->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($bundle->short_description ?? $bundle->description), 150))
@if($bundle->image_url)@section('og_image', $bundle->image_url)@endif

@php($try = fn ($v) => '₺' . number_format((float) $v, 2, ',', '.'))

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10">
    <nav class="flex items-center gap-1.5 text-sm text-bark/50 mb-6">
        <a href="{{ route('home') }}" class="hover:text-leaf-700">Anasayfa</a><span>/</span>
        <a href="{{ route('bundles.index') }}" class="hover:text-leaf-700">Hazır Kutular</a><span>/</span>
        <span class="text-bark font-500">{{ $bundle->name }}</span>
    </nav>

    <div class="grid lg:grid-cols-2 gap-8 lg:gap-12">
        {{-- Görsel --}}
        <div class="relative aspect-square rounded-3xl overflow-hidden bg-paper">
            @if($bundle->image_url)
                <img src="{{ $bundle->image_url }}" alt="{{ $bundle->name }}" class="size-full object-cover">
            @else
                <div class="size-full grid place-items-center text-leaf-300">
                    <svg class="size-24" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                </div>
            @endif
            <div class="absolute top-4 left-4 flex flex-col gap-1.5">
                @if($bundle->is_weekly)<span class="chip bg-leaf-600 text-white">Haftalık Kutu</span>@endif
                @if($bundle->discount_percent)<span class="chip bg-clay-500 text-white">%{{ $bundle->discount_percent }} indirim</span>@endif
            </div>
        </div>

        {{-- Bilgi --}}
        <div>
            <h1 class="font-display text-3xl sm:text-4xl font-700 text-bark leading-tight">{{ $bundle->name }}</h1>
            @if($bundle->short_description)<p class="mt-4 text-bark/70 leading-relaxed">{{ $bundle->short_description }}</p>@endif

            <div class="mt-6 flex items-baseline gap-3">
                <span class="font-display text-3xl font-700 text-leaf-800 tnum">{{ $try($bundle->price) }}</span>
                @if($bundle->compare_at_price && $bundle->compare_at_price > $bundle->price)
                    <span class="text-lg text-bark/40 line-through tnum">{{ $try($bundle->compare_at_price) }}</span>
                @endif
            </div>
            <p class="mt-1 text-xs text-bark/50">KDV dahil · kapınıza ücretsiz teslimat</p>

            <form action="{{ route('cart.addBundle') }}" method="POST" class="mt-6">
                @csrf
                <input type="hidden" name="bundle_id" value="{{ $bundle->id }}">
                <button class="btn-leaf w-full !rounded-full text-base">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272"/></svg>
                    Kutuyu Sepete Ekle
                </button>
            </form>

            {{-- Kutu içeriği --}}
            @if($bundle->items->isNotEmpty())
                <div class="mt-8">
                    <h2 class="font-700 text-bark mb-3">Kutu İçeriği</h2>
                    <ul class="rounded-xl border border-paper bg-white divide-y divide-paper">
                        @foreach($bundle->items as $item)
                            <li class="flex items-center justify-between px-4 py-3 text-sm">
                                <span class="flex items-center gap-2 text-bark/80">
                                    <svg class="size-4 text-leaf-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    @if($item->product)
                                        <a href="{{ route('product.show', $item->product->slug) }}" class="hover:text-leaf-700">{{ $item->label }}</a>
                                    @else
                                        {{ $item->label }}
                                    @endif
                                </span>
                                <span class="text-bark/40 tnum">{{ rtrim(rtrim(number_format($item->quantity,3,',','.'),'0'),',') }} adet</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($bundle->description)
                <div class="prose prose-sm max-w-none mt-8 text-bark/80 leading-relaxed">{!! $bundle->description !!}</div>
            @endif
        </div>
    </div>
</div>
@endsection
