@extends('layouts.storefront')

@section('title', 'Favorilerim — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10">
    <h1 class="font-display text-3xl font-600 text-bark mb-8">Favorilerim</h1>

    @if($products->isEmpty())
        <div class="rounded-3xl border border-dashed border-leaf-200 bg-white p-16 text-center">
            <span class="grid size-16 place-items-center rounded-full bg-clay-50 text-clay-400 mx-auto mb-4">
                <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke-width="1.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
            </span>
            <p class="font-display text-xl text-bark">Henüz favoriniz yok</p>
            <p class="mt-2 text-sm text-bark/60">Beğendiğiniz ürünleri kalbe dokunarak buraya ekleyebilirsiniz.</p>
            <a href="{{ route('home') }}" class="btn-leaf mt-6">Ürünleri keşfet</a>
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
            @foreach($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    @endif
</div>
@endsection
