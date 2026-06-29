@extends('layouts.storefront')

@section('title', ($product->meta_title ?: $product->name) . ' — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@section('meta_description', $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($product->short_description), 150))

@if($product->cover_url)@section('og_image', $product->cover_url)@endif

@push('schema')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product->name,
    'description' => strip_tags($product->short_description ?? $product->description ?? ''),
    'sku' => $product->sku,
    'image' => $product->cover_url,
    'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
    'category' => $product->category?->name,
    'offers' => [
        '@type' => 'Offer',
        'url' => route('product.show', $product->slug),
        'priceCurrency' => 'TRY',
        'price' => (string) $product->variants->min('price'),
        'availability' => ($product->variants->where('stock', '>', 0)->isNotEmpty() || $product->variants->where('track_stock', false)->isNotEmpty())
            ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
    ],
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
    'itemListElement' => array_values(array_filter([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Anasayfa', 'item' => route('home')],
        $product->category ? ['@type' => 'ListItem', 'position' => 2, 'name' => $product->category->name, 'item' => route('category.show', $product->category->slug)] : null,
        ['@type' => 'ListItem', 'position' => $product->category ? 3 : 2, 'name' => $product->name, 'item' => route('product.show', $product->slug)],
    ])),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
@php
    $images = $product->images;
    $variantsJson = $product->variants->map(fn ($v) => [
        'id' => $v->id,
        'name' => $v->name ?: ($v->unit_amount . ' ' . $v->unit->value),
        'price' => (float) $v->price,
        'compare' => $v->compare_at_price ? (float) $v->compare_at_price : null,
        'stock' => (float) $v->stock,
        'track' => (bool) $v->track_stock,
        'weight' => (bool) $v->is_weight_based,
    ])->values();
    $first = $variantsJson->first();
@endphp

<div class="mx-auto max-w-7xl px-4 py-8"
     x-data="productPage({{ $variantsJson->toJson() }})">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-sm text-bark/50 mb-6">
        <a href="{{ route('home') }}" class="hover:text-leaf-700">Anasayfa</a>
        <span>/</span>
        @if($product->category)
            <a href="{{ route('category.show', $product->category->slug) }}" class="hover:text-leaf-700">{{ $product->category->name }}</a>
            <span>/</span>
        @endif
        <span class="text-bark font-500 line-clamp-1">{{ $product->name }}</span>
    </nav>

    <div class="grid lg:grid-cols-2 gap-8 lg:gap-12">
        {{-- Galeri --}}
        <div x-data="{ active: 0 }">
            <div class="relative aspect-square rounded-3xl overflow-hidden bg-paper">
                @forelse($images as $i => $img)
                    <img x-show="active === {{ $i }}" src="{{ asset('storage/' . $img->path) }}" alt="{{ $img->alt ?: $product->name }}"
                         class="size-full object-cover" @if($i === 0) x-cloak @endif>
                @empty
                    <div class="size-full grid place-items-center text-leaf-300">
                        <svg class="size-24" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 19.5h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                    </div>
                @endforelse
            </div>
            @if($images->count() > 1)
                <div class="mt-4 flex gap-3">
                    @foreach($images as $i => $img)
                        <button @click="active = {{ $i }}" :class="active === {{ $i }} ? 'border-leaf-500' : 'border-paper'"
                                class="size-20 rounded-xl overflow-hidden border-2 transition">
                            <img src="{{ asset('storage/' . $img->path) }}" alt="" class="size-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Bilgi --}}
        <div>
            {{-- Etiketler --}}
            @if($product->tags->isNotEmpty())
                <div class="flex flex-wrap gap-1.5 mb-3">
                    @foreach($product->tags as $tag)
                        <span class="chip bg-leaf-50 text-leaf-700" @if($tag->color) style="background-color: {{ $tag->color }}1a; color: {{ $tag->color }}" @endif>{{ $tag->name }}</span>
                    @endforeach
                </div>
            @endif

            <h1 class="font-display text-3xl sm:text-4xl font-600 text-bark leading-tight">{{ $product->name }}</h1>

            @if($product->producer)
                <a href="#uretici" class="mt-2 inline-flex items-center gap-1.5 text-sm text-bark/60 hover:text-leaf-700">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    {{ $product->producer->name }}@if($product->producer->location) · {{ $product->producer->location }}@endif
                </a>
            @endif

            @if($product->short_description)
                <p class="mt-4 text-bark/70 leading-relaxed">{{ $product->short_description }}</p>
            @endif

            {{-- Fiyat --}}
            <div class="mt-6 flex items-baseline gap-3">
                <span class="font-display text-3xl font-700 text-leaf-800 tnum" x-text="formatPrice(current.price)"></span>
                <template x-if="current.compare && current.compare > current.price">
                    <span class="text-lg text-bark/40 line-through tnum" x-text="formatPrice(current.compare)"></span>
                </template>
                <template x-if="current.compare && current.compare > current.price">
                    <span class="chip bg-clay-500 text-white tnum" x-text="'%' + Math.round((1 - current.price/current.compare)*100) + ' indirim'"></span>
                </template>
            </div>
            <p class="mt-1 text-xs text-bark/50">KDV dahil fiyat</p>

            {{-- Varyant seçimi --}}
            @if($product->variants->count() > 1)
                <div class="mt-6">
                    <h3 class="text-sm font-600 text-bark mb-2">Seçenek</h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(v, idx) in variants" :key="v.id">
                            <button @click="select(idx)" type="button"
                                    :class="current.id === v.id ? 'border-leaf-500 bg-leaf-50 text-leaf-800' : 'border-paper text-bark hover:border-leaf-300'"
                                    class="rounded-xl border-2 px-4 py-2 text-sm font-600 transition">
                                <span x-text="v.name"></span>
                            </button>
                        </template>
                    </div>
                </div>
            @endif

            {{-- Stok --}}
            <p class="mt-4 text-sm flex items-center gap-2">
                <template x-if="inStock">
                    <span class="flex items-center gap-1.5 text-leaf-700 font-500"><span class="size-2 rounded-full bg-leaf-500"></span> Stokta</span>
                </template>
                <template x-if="!inStock">
                    <span class="flex items-center gap-1.5 text-clay-600 font-500"><span class="size-2 rounded-full bg-clay-500"></span> Tükendi</span>
                </template>
                @if($product->estimated_delivery)
                    <span class="text-bark/40">·</span>
                    <span class="text-bark/60">{{ $product->estimated_delivery }}</span>
                @endif
            </p>

            {{-- Adet + Sepete ekle --}}
            <div class="mt-6 flex items-stretch gap-3">
                <div class="flex items-center rounded-full border border-leaf-200 bg-white">
                    <button @click="qty = Math.max(1, qty - 1)" class="grid size-11 place-items-center text-bark hover:text-leaf-700" aria-label="Azalt">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                    </button>
                    <span class="w-10 text-center font-600 tnum" x-text="qty"></span>
                    <button @click="qty++" class="grid size-11 place-items-center text-bark hover:text-leaf-700" aria-label="Artır">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    </button>
                </div>

                <form action="{{ route('cart.add') }}" method="POST" class="flex-1"
                      @submit.prevent="addToCart($el)">
                    @csrf
                    <input type="hidden" name="variant_id" :value="current.id">
                    <input type="hidden" name="qty" :value="qty">
                    <button type="submit" :disabled="!inStock || adding"
                            class="btn-leaf w-full h-full text-base transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="added ? '!bg-leaf-600' : ''">
                        {{-- sepet ikonu (normal) --}}
                        <svg x-show="!added" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272"/></svg>
                        {{-- onay ikonu (eklendi) --}}
                        <svg x-show="added" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        <span x-text="adding ? 'Ekleniyor…' : (added ? 'Sepete Eklendi' : 'Sepete Ekle')">Sepete Ekle</span>
                    </button>
                </form>

                <form action="{{ route('wishlist.toggle') }}" method="POST"
                      x-data="{ fav: {{ app(\App\Services\Wishlist\WishlistService::class)->has($product->id) ? 'true':'false' }} }"
                      @submit.prevent="fav = !fav; fetch($el.action, { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body:new FormData($el) })">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button type="submit" class="grid size-12 place-items-center rounded-full border border-leaf-200 bg-white hover:border-clay-300" aria-label="Favori">
                        <svg class="size-6 transition" :class="fav ? 'text-clay-500 fill-clay-500' : 'text-bark/50 fill-none'" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                    </button>
                </form>
            </div>

            {{-- WhatsApp ile sipariş (seçili varyant + adet mesaja otomatik yazılır) --}}
            @php
                // Numarayı wa.me formatına normalize et: rakam dışını sil, baştaki 0'ı at, ülke kodu (90) yoksa ekle.
                $wa = preg_replace('/\D/', '', (string) app(\App\Settings\ContactSettings::class)->whatsapp);
                if ($wa !== '') {
                    if (str_starts_with($wa, '0')) { $wa = substr($wa, 1); }
                    if (! str_starts_with($wa, '90') && strlen($wa) <= 10) { $wa = '90' . $wa; }
                }
            @endphp
            @if($wa)
                <a target="_blank" rel="noopener nofollow"
                   :href="'https://wa.me/{{ $wa }}?text=' + encodeURIComponent('Merhaba, ' + @js($product->name) + ' (' + current.name + ') ürününden ' + qty + ' adet sipariş vermek istiyorum.\n' + @js(url()->current()))"
                   class="group relative mt-3 flex items-center justify-center gap-3 overflow-hidden rounded-full px-5 py-3.5
                          text-white font-700 shadow-[0_8px_22px_-6px_rgba(37,211,102,0.55)] transition-all duration-200
                          hover:shadow-[0_12px_28px_-6px_rgba(37,211,102,0.7)] hover:-translate-y-0.5 active:scale-[0.99]"
                   style="background:linear-gradient(135deg,#25D366 0%,#1ebe5d 60%,#12a350 100%);">
                    {{-- Parıltı efekti --}}
                    <span class="pointer-events-none absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 ease-out"
                          style="background:linear-gradient(100deg,transparent 35%,rgba(255,255,255,0.35) 50%,transparent 65%);"></span>
                    {{-- Çevrimiçi nabız --}}
                    <span class="relative flex size-2.5 shrink-0">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white/70"></span>
                        <span class="relative inline-flex size-2.5 rounded-full bg-white"></span>
                    </span>
                    <svg class="relative size-6 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.513 5.26l-.999 3.648 3.484-.913zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    <span class="relative flex flex-col leading-tight">
                        <span class="text-base">WhatsApp ile Sipariş Ver</span>
                        <span class="text-[11px] font-500 text-white/85">Anında yanıt · hızlı &amp; kolay</span>
                    </span>
                </a>
            @endif

            {{-- Buton altı güven satırı --}}
            <ul class="mt-5 space-y-2 text-sm text-bark/70">
                <li class="flex items-center gap-2"><svg class="size-4 text-leaf-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>{{ number_format(app(\App\Settings\GeneralSettings::class)->free_shipping_threshold,0,",",".") }} TL üzeri ücretsiz kargo</li>
                <li class="flex items-center gap-2"><svg class="size-4 text-leaf-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Güvenli paketleme ve soğuk zincir</li>
                <li class="flex items-center gap-2"><svg class="size-4 text-leaf-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>3D Secure ile güvenli ödeme</li>
            </ul>

            {{-- Güven blokları --}}
            <div class="mt-8 grid sm:grid-cols-2 gap-3">
                @if($product->certificate_no)
                    <div class="flex items-center gap-3 rounded-xl border border-leaf-100 bg-leaf-50/50 p-3">
                        <svg class="size-7 text-leaf-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        <div>
                            <p class="text-xs text-bark/50">Organik Sertifika No</p>
                            <p class="font-600 text-sm text-bark tnum">{{ $product->certificate_no }}</p>
                        </div>
                    </div>
                @endif
                @if($product->certificates->where('type','analysis')->isNotEmpty())
                    <a href="#belgeler" class="flex items-center gap-3 rounded-xl border border-leaf-100 bg-leaf-50/50 p-3 hover:border-leaf-300">
                        <svg class="size-7 text-leaf-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        <div>
                            <p class="text-xs text-bark/50">Pestisit Analizi</p>
                            <p class="font-600 text-sm text-bark">Raporu görüntüle</p>
                        </div>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Açıklama / İçindekiler / Saklama --}}
    <div class="mt-16 grid lg:grid-cols-3 gap-8" x-data="{ tab: 'desc' }">
        <div class="lg:col-span-2">
            <div class="flex gap-1 border-b border-paper">
                <button @click="tab='desc'" :class="tab==='desc' ? 'border-leaf-600 text-leaf-800' : 'border-transparent text-bark/50'" class="border-b-2 px-4 py-3 font-600 text-sm transition">Açıklama</button>
                @if($product->ingredients)<button @click="tab='ing'" :class="tab==='ing' ? 'border-leaf-600 text-leaf-800' : 'border-transparent text-bark/50'" class="border-b-2 px-4 py-3 font-600 text-sm transition">İçindekiler</button>@endif
                @if($product->storage_info)<button @click="tab='store'" :class="tab==='store' ? 'border-leaf-600 text-leaf-800' : 'border-transparent text-bark/50'" class="border-b-2 px-4 py-3 font-600 text-sm transition">Saklama</button>@endif
                <button @click="tab='delivery'" :class="tab==='delivery' ? 'border-leaf-600 text-leaf-800' : 'border-transparent text-bark/50'" class="border-b-2 px-4 py-3 font-600 text-sm transition">Teslimat &amp; İade</button>
            </div>
            <div class="prose prose-sm max-w-none mt-5 text-bark/80 leading-relaxed">
                <div x-show="tab==='desc'">{!! $product->description ?: '<p class="text-bark/50">Bu ürün için detaylı açıklama yakında eklenecek.</p>' !!}</div>
                @if($product->ingredients)<div x-show="tab==='ing'" x-cloak>{!! nl2br(e($product->ingredients)) !!}</div>@endif
                @if($product->storage_info)<div x-show="tab==='store'" x-cloak>{!! nl2br(e($product->storage_info)) !!}</div>@endif
                <div x-show="tab==='delivery'" x-cloak>
                    <ul>
                        <li>Siparişiniz seçtiğiniz teslimat gününe göre hazırlanır; taze ürünlerde soğuk zincir korunur.</li>
                        <li>{{ number_format(app(\App\Settings\GeneralSettings::class)->free_shipping_threshold,0,",",".") }} TL ve üzeri siparişlerde kargo ücretsizdir.</li>
                        <li>Standart ürünlerde 14 gün içinde cayma hakkınız vardır.</li>
                        <li>Çabuk bozulan/taze gıdalarda cayma hakkının niteliği <a href="{{ url('/sayfa/iptal-iade-kosullari') }}" class="text-leaf-700">İptal &amp; İade</a> sayfamızda açıklanmıştır.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Belgeler --}}
        @if($product->certificates->isNotEmpty())
            <div id="belgeler">
                <h3 class="font-display text-xl font-600 text-bark mb-4">Sertifika &amp; Analizler</h3>
                <div class="space-y-2">
                    @foreach($product->certificates as $cert)
                        <a href="{{ asset('storage/' . $cert->file) }}" target="_blank" rel="noopener"
                           class="flex items-center gap-3 rounded-xl border border-paper bg-white p-3 hover:border-leaf-300">
                            <span class="grid size-10 place-items-center rounded-lg bg-leaf-50 text-leaf-600 shrink-0">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="font-600 text-sm text-bark truncate">{{ $cert->title }}</p>
                                <p class="text-xs text-bark/50">{{ $cert->type === 'analysis' ? 'Analiz Raporu' : 'Sertifika' }}</p>
                            </div>
                            <svg class="size-4 text-bark/40" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Üretici --}}
    @if($product->producer && $product->producer->story)
        <div id="uretici" class="mt-16 rounded-3xl bg-paper p-8 sm:p-10">
            <div class="flex items-center gap-4 mb-4">
                <div class="size-16 rounded-full bg-leaf-100 overflow-hidden shrink-0">
                    @if($product->producer->image)<img src="{{ asset('storage/' . $product->producer->image) }}" alt="" class="size-full object-cover">@endif
                </div>
                <div>
                    <p class="text-xs text-bark/50">Üretici</p>
                    <h3 class="font-display text-xl font-600 text-bark">{{ $product->producer->name }}</h3>
                </div>
            </div>
            <div class="prose prose-sm max-w-none text-bark/70">{!! $product->producer->story !!}</div>
        </div>
    @endif

    {{-- İlgili ürünler --}}
    @if($related->isNotEmpty())
        <x-product-section title="Benzer Ürünler" :products="$related" accent="leaf" class="!px-0" />
    @endif
</div>

@push('scripts')
<script>
    function productPage(variants) {
        return {
            variants,
            qty: 1,
            adding: false,
            added: false,
            _t: null,
            current: variants[0] ?? { id: null, price: 0, compare: null, stock: 0, track: false, weight: false },
            get inStock() { return !this.current.track || this.current.stock > 0; },
            select(idx) { this.current = this.variants[idx]; this.qty = 1; },
            formatPrice(v) { return new Intl.NumberFormat('tr-TR', { style:'currency', currency:'TRY' }).format(v); },
            addToCart(form) {
                if (!this.inStock || this.adding) return;
                this.adding = true;
                fetch(form.action, { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body:new FormData(form) })
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(d => {
                        this.adding = false;
                        this.added = true;
                        window.dispatchEvent(new CustomEvent('cart-updated', { detail: d.count }));
                        window.dispatchEvent(new CustomEvent('toast', { detail: d.message || 'Ürün sepete eklendi.' }));
                        clearTimeout(this._t);
                        this._t = setTimeout(() => { this.added = false; }, 2200);
                    })
                    .catch(() => {
                        this.adding = false;
                        window.dispatchEvent(new CustomEvent('toast', { detail: 'Bir hata oluştu, lütfen tekrar deneyin.' }));
                    });
            },
        }
    }
</script>
@endpush
@endsection
