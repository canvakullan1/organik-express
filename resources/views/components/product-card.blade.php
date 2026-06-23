@props(['product'])

@php
    $variant = $product->main_variant;
    $cover = $product->cover_url;
    $discount = $product->discount_percent;
    $inWishlist = app(\App\Services\Wishlist\WishlistService::class)->has($product->id);
@endphp

<div class="group relative flex flex-col rounded-xl border border-paper bg-white overflow-hidden card-lift"
     x-data="{ fav: {{ $inWishlist ? 'true' : 'false' }} }">

    {{-- Görsel --}}
    <a href="{{ route('product.show', $product->slug) }}" class="relative block aspect-square overflow-hidden media-placeholder">
        @if($cover)
            <img src="{{ $cover }}" alt="{{ $product->name }}" loading="lazy"
                 class="size-full object-cover transition duration-500 group-hover:scale-[1.04]">
        @else
            <div class="size-full grid place-items-center">
                <svg class="size-10 text-leaf-300/70" fill="none" viewBox="0 0 24 24" stroke-width="1.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 0 0 0-18m0 18a9 9 0 0 1 0-18m0 18c2.5-2 4-5.5 4-9s-1.5-7-4-9m0 18c-2.5-2-4-5.5-4-9s1.5-7 4-9"/></svg>
            </div>
        @endif

        {{-- Rozetler --}}
        <div class="absolute top-3 left-3 flex flex-col gap-1.5">
            @if($discount)
                <span class="chip bg-clay-500 text-white tnum">%{{ $discount }} indirim</span>
            @endif
            @if($product->is_new)
                <span class="chip bg-leaf-600 text-white">Yeni</span>
            @endif
        </div>
    </a>

    {{-- Favori --}}
    <form action="{{ route('wishlist.toggle') }}" method="POST" class="absolute top-3 right-3"
          @submit.prevent="
              fav = !fav;
              fetch($el.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: new FormData($el) });
          ">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <button type="submit" class="grid size-9 place-items-center rounded-full bg-white/90 backdrop-blur shadow-sm hover:bg-white" aria-label="Favorilere ekle">
            <svg class="size-5 transition" :class="fav ? 'text-clay-500 fill-clay-500' : 'text-bark/50 fill-none'" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
        </button>
    </form>

    {{-- İçerik --}}
    <div class="flex flex-1 flex-col p-4">
        @if($product->producer)
            <p class="mb-1 text-[11px] font-600 uppercase tracking-wide text-bark/40">{{ $product->producer->name }}</p>
        @endif

        <h3 class="text-[15px] font-600 leading-snug text-bark line-clamp-2">
            <a href="{{ route('product.show', $product->slug) }}" class="hover:text-leaf-700">{{ $product->name }}</a>
        </h3>

        @if($variant && $variant->name)
            <p class="mt-0.5 text-xs text-bark/45">{{ $variant->name }}</p>
        @endif

        <div class="mt-auto pt-3">
            <x-price :price="$variant?->price" :compare="$variant?->compare_at_price" />

            @if($variant)
                <form action="{{ route('cart.add') }}" method="POST" class="mt-3"
                      @submit.prevent="
                          fetch($el.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: new FormData($el) })
                              .then(r => r.json()).then(d => { window.dispatchEvent(new CustomEvent('cart-updated', { detail: d.count })); $dispatch('toast', d.message); });
                      ">
                    @csrf
                    <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-full bg-leaf-600 py-2.5 text-sm font-700 text-white transition hover:bg-leaf-700 active:scale-[0.98]">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                        Sepete Ekle
                    </button>
                </form>
            @else
                <p class="mt-3 text-center text-sm text-bark/40 py-2.5">Stokta yok</p>
            @endif
        </div>
    </div>
</div>
