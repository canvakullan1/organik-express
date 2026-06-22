@extends('layouts.storefront')

@section('title', 'Üreticilerimiz — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@section('meta_description', 'Birlikte çalıştığımız üreticiler ve çiftlikler. Üreticisi belli, izlenebilir, şeffaf organik gıda.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10">
    <header class="max-w-2xl mb-8">
        
        <h1 class="font-display text-3xl sm:text-4xl font-700 text-bark">Üreticilerimiz</h1>
        <p class="mt-2 text-bark/60">Her ürünün arkasında bir hikâye, bir çiftlik, bir emek var. Üreticisi belli, izlenebilir ürünler.</p>
    </header>

    @if($producers->isEmpty())
        <div class="rounded-2xl border border-dashed border-leaf-200 bg-white p-12 text-center">
            <p class="text-bark/60">Üretici bilgileri yakında eklenecek.</p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($producers as $producer)
                <a href="{{ route('producer.show', $producer->slug) }}" class="group rounded-2xl border border-paper bg-white overflow-hidden card-lift">
                    <div class="aspect-[16/10] bg-paper overflow-hidden">
                        @if($producer->image)
                            <img src="{{ asset('storage/' . $producer->image) }}" alt="{{ $producer->name }}" loading="lazy" class="size-full object-cover group-hover:scale-105 transition duration-500">
                        @else
                            <div class="size-full grid place-items-center text-leaf-200">
                                <svg class="size-14" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                            </div>
                        @endif
                    </div>
                    <div class="p-5">
                        <h2 class="font-display text-lg font-700 text-bark group-hover:text-leaf-700">{{ $producer->name }}</h2>
                        @if($producer->location)<p class="text-sm text-bark/50 flex items-center gap-1 mt-0.5"><svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>{{ $producer->location }}</p>@endif
                        @if($producer->short_description)<p class="mt-2 text-sm text-bark/60 line-clamp-2">{{ $producer->short_description }}</p>@endif
                        <p class="mt-3 text-xs font-600 text-leaf-700">{{ $producer->products_count }} ürün →</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
