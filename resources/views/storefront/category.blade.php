@extends('layouts.storefront')

@section('title', ($category->meta_title ?: $category->name) . ' — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@section('meta_description', $category->meta_description ?: 'Organik ' . $category->name . ' ürünleri.')

@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
    'itemListElement' => array_values(array_filter([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Anasayfa', 'item' => route('home')],
        $category->parent ? ['@type' => 'ListItem', 'position' => 2, 'name' => $category->parent->name, 'item' => route('category.show', $category->parent->slug)] : null,
        ['@type' => 'ListItem', 'position' => $category->parent ? 3 : 2, 'name' => $category->name, 'item' => route('category.show', $category->slug)],
    ])),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-sm text-bark/50 mb-4">
        <a href="{{ route('home') }}" class="hover:text-leaf-700">Anasayfa</a>
        <span>/</span>
        @if($category->parent)
            <a href="{{ route('category.show', $category->parent->slug) }}" class="hover:text-leaf-700">{{ $category->parent->name }}</a>
            <span>/</span>
        @endif
        <span class="text-bark font-500">{{ $category->name }}</span>
    </nav>

    <header class="mb-8">
        <h1 class="font-display text-3xl sm:text-4xl font-600 text-bark">{{ $category->name }}</h1>
        @if($category->description)
            <div class="prose prose-sm max-w-2xl mt-2 text-bark/60 prose-p:my-1">{!! $category->description !!}</div>
        @endif
    </header>

    <div class="grid lg:grid-cols-4 gap-8" x-data="{ filtersOpen: false }">
        {{-- Filtreler --}}
        <aside class="lg:col-span-1">
            <div class="lg:hidden mb-4">
                <button @click="filtersOpen = !filtersOpen" class="btn-ghost w-full">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
                    Filtreler
                </button>
            </div>

            <form method="GET" :class="filtersOpen ? 'block' : 'hidden lg:block'" class="space-y-6 rounded-2xl border border-paper bg-white p-5 sticky top-28">
                {{-- Fiyat --}}
                <div>
                    <h3 class="font-600 text-bark mb-3">Fiyat Aralığı</h3>
                    <div class="flex items-center gap-2">
                        <input type="number" name="min" value="{{ request('min') }}" placeholder="Min" min="0"
                               class="w-full rounded-lg border border-leaf-200 px-3 py-2 text-sm tnum focus:border-leaf-400 focus:outline-none">
                        <span class="text-bark/40">—</span>
                        <input type="number" name="max" value="{{ request('max') }}" placeholder="Max" min="0"
                               class="w-full rounded-lg border border-leaf-200 px-3 py-2 text-sm tnum focus:border-leaf-400 focus:outline-none">
                    </div>
                </div>

                {{-- Etiketler --}}
                @if($tags->isNotEmpty())
                    <div>
                        <h3 class="font-600 text-bark mb-3">Özellikler</h3>
                        <div class="space-y-2">
                            @foreach($tags as $tag)
                                <label class="flex items-center gap-2.5 text-sm cursor-pointer">
                                    <input type="checkbox" name="tag[]" value="{{ $tag->id }}"
                                           @checked(in_array($tag->id, (array) request('tag')))
                                           class="rounded border-leaf-300 text-leaf-600 focus:ring-leaf-400">
                                    <span>{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Marka --}}
                @if($brands->isNotEmpty())
                    <div>
                        <h3 class="font-600 text-bark mb-3">Marka</h3>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach($brands as $brand)
                                <label class="flex items-center gap-2.5 text-sm cursor-pointer">
                                    <input type="checkbox" name="brand[]" value="{{ $brand->id }}"
                                           @checked(in_array($brand->id, (array) request('brand')))
                                           class="rounded border-leaf-300 text-leaf-600 focus:ring-leaf-400">
                                    <span>{{ $brand->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="space-y-2 border-t border-paper pt-4">
                    <label class="flex items-center gap-2.5 text-sm cursor-pointer">
                        <input type="checkbox" name="discounted" value="1" @checked(request('discounted')) class="rounded border-leaf-300 text-leaf-600 focus:ring-leaf-400">
                        <span>Sadece indirimliler</span>
                    </label>
                    <label class="flex items-center gap-2.5 text-sm cursor-pointer">
                        <input type="checkbox" name="in_stock" value="1" @checked(request('in_stock')) class="rounded border-leaf-300 text-leaf-600 focus:ring-leaf-400">
                        <span>Stokta olanlar</span>
                    </label>
                </div>

                @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif

                <div class="flex gap-2">
                    <button type="submit" class="btn-leaf flex-1">Uygula</button>
                    <a href="{{ route('category.show', $category->slug) }}" class="btn-ghost">Temizle</a>
                </div>
            </form>
        </aside>

        {{-- Ürünler --}}
        <div class="lg:col-span-3">
            <div class="flex items-center justify-between gap-4 mb-5">
                <p class="text-sm text-bark/60 tnum">{{ $products->total() }} ürün</p>
                <form method="GET" x-data @change="$el.submit()">
                    @foreach(request()->except('sort', 'page') as $k => $v)
                        @foreach((array) $v as $vv)<input type="hidden" name="{{ $k }}{{ is_array($v) ? '[]' : '' }}" value="{{ $vv }}">@endforeach
                    @endforeach
                    <select name="sort" class="rounded-full border border-leaf-200 bg-white py-2 pl-4 pr-9 text-sm font-500 focus:border-leaf-400 focus:outline-none">
                        <option value="default" @selected(request('sort')==='default')>Önerilen sıralama</option>
                        <option value="price_asc" @selected(request('sort')==='price_asc')>Fiyat: Düşükten yükseğe</option>
                        <option value="price_desc" @selected(request('sort')==='price_desc')>Fiyat: Yüksekten düşüğe</option>
                        <option value="newest" @selected(request('sort')==='newest')>En yeniler</option>
                    </select>
                </form>
            </div>

            @if($products->isEmpty())
                <div class="rounded-2xl border border-dashed border-leaf-200 bg-white p-12 text-center">
                    <p class="font-display text-xl text-bark">Bu kategoride henüz ürün yok</p>
                    <p class="mt-2 text-sm text-bark/60">Filtreleri değiştirmeyi deneyin ya da diğer kategorilere göz atın.</p>
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 sm:gap-5">
                    @foreach($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
                <div class="mt-10">{{ $products->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
