@extends('layouts.storefront')

@section('title', ($query ? $query . ' — Arama' : 'Arama') . ' — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10">
    <form action="{{ route('search.index') }}" method="GET" class="relative max-w-2xl mb-8">
        <input type="search" name="q" value="{{ $query }}" autofocus placeholder="Ürün, kategori veya marka ara…"
               class="w-full rounded-full border border-leaf-200 bg-white py-3.5 pl-12 pr-4 focus:border-leaf-400 focus:outline-none focus:ring-2 focus:ring-leaf-100">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-leaf-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
    </form>

    @if($query === '')
        <p class="text-bark/60">Aramak istediğiniz ürünü yukarıya yazın.</p>
    @else
        <h1 class="font-display text-2xl font-600 text-bark mb-6">
            "{{ $query }}" için {{ $products->total() }} sonuç
        </h1>

        @if($products->isEmpty())
            <div class="rounded-2xl border border-dashed border-leaf-200 bg-white p-12 text-center">
                <p class="font-display text-xl text-bark">Sonuç bulunamadı</p>
                <p class="mt-2 text-sm text-bark/60">Farklı bir kelime deneyin ya da kategorilere göz atın.</p>
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
                @foreach($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
            <div class="mt-10">{{ $products->links() }}</div>
        @endif
    @endif
</div>
@endsection
