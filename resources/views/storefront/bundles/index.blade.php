@extends('layouts.storefront')

@section('title', 'Hazır Kutular — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@section('meta_description', 'Haftalık lezzet dolu hazır organik kutular. Özenle seçilip paketlenir, kapınıza ücretsiz teslim edilir.')

@php($try = fn ($v) => '₺' . number_format((float) $v, 2, ',', '.'))

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10">
    <header class="max-w-2xl mb-8">
        
        <h1 class="font-display text-3xl sm:text-4xl font-700 text-bark">Hazır Kutular</h1>
        <p class="mt-2 text-bark/60">İhtiyaçlarınıza uygun, her hafta özenle seçilip paketlenen organik kutular — kapınıza ücretsiz teslimat.</p>
    </header>

    @if($bundles->isEmpty())
        <div class="rounded-2xl border border-dashed border-leaf-200 bg-white p-12 text-center">
            <p class="text-bark/60">Hazır kutular yakında eklenecek.</p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($bundles as $bundle)
                <div class="group flex flex-col rounded-2xl border border-paper bg-white overflow-hidden card-lift">
                    <a href="{{ route('bundle.show', $bundle->slug) }}" class="relative block aspect-[4/3] bg-paper overflow-hidden">
                        @if($bundle->image_url)
                            <img src="{{ $bundle->image_url }}" alt="{{ $bundle->name }}" loading="lazy" class="size-full object-cover group-hover:scale-105 transition duration-500">
                        @else
                            <div class="size-full grid place-items-center text-leaf-200">
                                <svg class="size-16" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                            </div>
                        @endif
                        <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                            @if($bundle->is_weekly)<span class="chip bg-leaf-600 text-white">Haftalık</span>@endif
                            @if($bundle->discount_percent)<span class="chip bg-clay-500 text-white">%{{ $bundle->discount_percent }} indirim</span>@endif
                        </div>
                    </a>
                    <div class="flex flex-1 flex-col p-5">
                        <h2 class="font-display text-lg font-700 text-bark group-hover:text-leaf-700"><a href="{{ route('bundle.show', $bundle->slug) }}">{{ $bundle->name }}</a></h2>
                        @if($bundle->short_description)<p class="mt-1 text-sm text-bark/60 line-clamp-2">{{ $bundle->short_description }}</p>@endif
                        <p class="mt-2 text-xs text-bark/45">{{ $bundle->items_count }} ürün içerir</p>
                        <div class="mt-auto pt-4 flex items-center justify-between gap-2">
                            <div>
                                <span class="font-display text-xl font-700 text-leaf-800 tnum">{{ $try($bundle->price) }}</span>
                                @if($bundle->compare_at_price && $bundle->compare_at_price > $bundle->price)
                                    <span class="ml-1 text-sm text-bark/40 line-through tnum">{{ $try($bundle->compare_at_price) }}</span>
                                @endif
                            </div>
                            <form action="{{ route('cart.addBundle') }}" method="POST">
                                @csrf
                                <input type="hidden" name="bundle_id" value="{{ $bundle->id }}">
                                <button class="rounded-full bg-leaf-600 px-4 py-2 text-sm font-700 text-white hover:bg-leaf-700 transition">Sepete Ekle</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
