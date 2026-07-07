@extends('layouts.storefront')

@section('content')

    {{-- Hero — tam genişlik slider --}}
    <section class="mx-auto max-w-7xl px-4 pt-6">
        @if($heroBanners->isNotEmpty())
            <div class="relative overflow-hidden rounded-2xl bg-leaf-100 h-[300px] sm:h-[400px] lg:h-[460px]"
                 x-data="{ active: 0, count: {{ $heroBanners->count() }},
                           next() { this.active = (this.active + 1) % this.count },
                           prev() { this.active = (this.active - 1 + this.count) % this.count } }"
                 x-init="if (count > 1) setInterval(() => next(), 5000)">
                @foreach($heroBanners as $i => $banner)
                    <a @if($banner->link) href="{{ $banner->link }}" @endif
                       :class="active === {{ $i }} ? 'opacity-100 z-[1]' : 'opacity-0 z-0 pointer-events-none'"
                       class="absolute inset-0 block transition-opacity duration-700 ease-in-out">
                        <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="size-full object-cover">
                        @if($banner->title || $banner->subtitle)
                            <div class="absolute inset-0 bg-gradient-to-r from-leaf-950/70 via-leaf-950/25 to-transparent flex items-center">
                                <div class="p-6 sm:p-12 lg:p-16 max-w-xl text-white">
                                    @if($banner->title)<h2 class="font-display text-3xl sm:text-4xl lg:text-6xl font-700 leading-[1.05] drop-shadow">{{ $banner->title }}</h2>@endif
                                    @if($banner->subtitle)<p class="mt-3 sm:mt-4 text-base sm:text-lg text-leaf-50/95">{{ $banner->subtitle }}</p>@endif
                                    @if($banner->button_text)<span class="btn-leaf bg-white !text-leaf-800 hover:bg-leaf-50 mt-5 sm:mt-7 !px-7 !py-3 text-base">{{ $banner->button_text }}</span>@endif
                                </div>
                            </div>
                        @endif
                    </a>
                @endforeach

                @if($heroBanners->count() > 1)
                    {{-- Ok tuşları --}}
                    <button @click.prevent="prev()" class="absolute left-3 top-1/2 -translate-y-1/2 z-10 grid size-10 place-items-center rounded-full bg-white/85 text-leaf-800 shadow hover:bg-white transition" aria-label="Önceki">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    </button>
                    <button @click.prevent="next()" class="absolute right-3 top-1/2 -translate-y-1/2 z-10 grid size-10 place-items-center rounded-full bg-white/85 text-leaf-800 shadow hover:bg-white transition" aria-label="Sonraki">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </button>
                    {{-- Noktalar --}}
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10">
                        @foreach($heroBanners as $i => $b)
                            <button @click.prevent="active = {{ $i }}" :class="active === {{ $i }} ? 'bg-white w-7' : 'bg-white/60 w-2.5'" class="h-2.5 rounded-full transition-all" aria-label="Slayt {{ $i + 1 }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="relative overflow-hidden rounded-2xl bg-leaf-800 text-white aspect-[16/6] min-h-[300px] flex items-center">
                <div class="relative z-10 p-8 sm:p-14 max-w-xl">
                    <span class="chip bg-white/15 text-white w-fit mb-4 backdrop-blur">Sertifikalı &amp; Analizli</span>
                    <h1 class="font-display text-4xl sm:text-5xl font-700 leading-[1.05]">Çiftlikten sofraya<span class="text-clay-300">.</span></h1>
                    <p class="mt-4 text-leaf-100/90 leading-relaxed">Üreticisi belli, izlenebilir, pestisit analizli organik gıda.</p>
                    <a href="{{ route('search.index') }}" class="btn-leaf bg-white !text-leaf-800 hover:bg-leaf-50 mt-6">Alışverişe Başla</a>
                </div>
                <div class="absolute right-0 bottom-0 w-1/2 h-full opacity-20" style="background-image: radial-gradient(circle at 70% 60%, var(--color-leaf-400), transparent 60%);"></div>
            </div>
        @endif

        {{-- Promo şeridi (hero altı, 3'lü aksiyon kartları) --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
            {{-- 1 · Haftalık Kutular (vurgulu koyu kart) --}}
            <a href="{{ route('bundles.index') }}"
               class="group relative overflow-hidden rounded-2xl bg-leaf-800 p-6 text-white transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_-10px_rgb(28_35_30/0.35)] sm:col-span-2 lg:col-span-1">
                <svg class="absolute -right-5 -bottom-6 size-32 text-white/[0.07] transition-transform duration-500 group-hover:scale-110 group-hover:rotate-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                <p class="text-[11px] font-700 uppercase tracking-[0.1em] text-leaf-300">Her hafta taze</p>
                <h3 class="mt-1.5 font-display text-xl font-700 leading-snug">Haftalık Hazır Kutular</h3>
                <p class="mt-1 text-sm text-leaf-100/75">Tarladan kapına, özenle seçilip paketlenir.</p>
                <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-700 text-white">
                    Kutuları Keşfet
                    <svg class="size-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </span>
            </a>

            {{-- 2 · %10 erken sipariş --}}
            <a href="{{ url('/sayfa/teslimat-dagitim') }}"
               class="group relative overflow-hidden rounded-2xl bg-clay-100 p-6 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_-12px_rgb(28_35_30/0.18)]">
                <span class="absolute -right-3 -top-5 font-display text-[88px] font-700 leading-none text-clay-500/15 select-none tnum transition-transform duration-500 group-hover:scale-105">%10</span>
                <p class="text-[11px] font-700 uppercase tracking-[0.1em] text-clay-600">Erken sipariş avantajı</p>
                <h3 class="mt-1.5 font-display text-xl font-700 leading-snug text-clay-900">%10 İndirim Kazan</h3>
                <p class="mt-1 text-sm text-clay-800/65">Teslimat gününden 1 gün önce sipariş ver.</p>
                <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-700 text-clay-700">
                    Teslimat Günleri
                    <svg class="size-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </span>
            </a>

            {{-- 3 · Üreticiler --}}
            <a href="{{ route('producers.index') }}"
               class="group relative overflow-hidden rounded-2xl bg-leaf-100 p-6 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_-12px_rgb(28_35_30/0.18)]">
                <svg class="absolute -right-4 -bottom-5 size-28 text-leaf-600/10 transition-transform duration-500 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 0 0 0-18m0 18a9 9 0 0 1 0-18m0 18c2.5-2 4-5.5 4-9s-1.5-7-4-9m0 18c-2.5-2-4-5.5-4-9s1.5-7 4-9"/></svg>
                <p class="text-[11px] font-700 uppercase tracking-[0.1em] text-leaf-600">Sertifikalı &amp; izlenebilir</p>
                <h3 class="mt-1.5 font-display text-xl font-700 leading-snug text-leaf-900">Üreticisinden Sofrana</h3>
                <p class="mt-1 text-sm text-leaf-800/65">Her ürünün arkasında tanıdığımız bir çiftlik var.</p>
                <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-700 text-leaf-700">
                    Üreticilerimiz
                    <svg class="size-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </span>
            </a>
        </div>
    </section>

    {{-- Güven rozetleri şeridi --}}
    <section class="mx-auto max-w-7xl px-4 mt-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            @foreach([
                ['M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', '%100 Doğal & Sertifikalı', 'Organik sertifika & analiz'],
                ['M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Z', 'Güvenli Ödeme', '3D Secure · iyzico/PayTR'],
                ['M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25', 'Ücretsiz Kargo', number_format(app(\App\Settings\GeneralSettings::class)->free_shipping_threshold,0,",",".") . ' TL üzeri'],
                ['M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z', 'Taze Garanti', 'Soğuk zincirle taze teslim'],
            ] as [$icon, $title, $sub])
                <div class="flex items-center gap-3 rounded-xl border border-paper bg-white px-4 py-3.5">
                    <span class="grid size-10 place-items-center rounded-full bg-leaf-50 text-leaf-600 shrink-0">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="font-700 text-sm text-bark leading-tight">{{ $title }}</p>
                        <p class="text-xs text-bark/50">{{ $sub }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Fotoğraflı kategori kartları (görseller admin → Kategoriler'den yönetilir) --}}
    <section class="mx-auto max-w-7xl px-4 py-10">
        <div class="mb-5 flex items-baseline justify-between gap-4 border-b border-paper pb-3">
            <h2 class="font-display text-xl sm:text-2xl font-700 tracking-tight text-bark">Kategoriler</h2>
            <a href="{{ route('search.index') }}" class="shrink-0 text-sm font-600 text-leaf-700 hover:text-leaf-800">Tümünü Gör <span aria-hidden="true">→</span></a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-3 sm:gap-4">
            @foreach($shortcutCategories as $cat)
                <a href="{{ route('category.show', $cat->slug) }}"
                   class="group relative block aspect-square overflow-hidden rounded-2xl bg-leaf-100 shadow-sm">
                    @if($cat->image)
                        <img src="{{ asset('storage/' . $cat->image) }}" alt="{{ $cat->name }}" loading="lazy"
                             class="size-full object-cover transition duration-500 group-hover:scale-110">
                    @else
                        <div class="size-full grid place-items-center bg-leaf-600 text-white/80">
                            <svg class="size-12" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 11.204 2.04a1.125 1.125 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
                        </div>
                    @endif
                    {{-- İsim — alt gradient üstünde --}}
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent p-3 pt-8">
                        <span class="block text-sm font-700 text-white leading-tight">{{ $cat->name }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Nasıl Çalışır + Teslimat --}}
    <section class="mx-auto max-w-7xl px-4 py-12">
        <div class="rounded-3xl bg-leaf-800 text-white overflow-hidden">
            <div class="grid lg:grid-cols-2">
                <div class="p-8 sm:p-12">
                    <span class="chip bg-white/15 text-white w-fit mb-4 backdrop-blur">Haftalık taze kutu</span>
                    <h2 class="font-display text-2xl sm:text-3xl font-700 leading-tight">Nasıl Çalışır?</h2>
                    <div class="mt-6 space-y-6">
                        <div class="flex gap-4">
                            <span class="grid size-9 place-items-center rounded-full bg-white text-leaf-800 font-700 shrink-0">1</span>
                            <div>
                                <h3 class="font-700">Haftalık lezzet dolu bir kutu seçin</h3>
                                <p class="mt-1 text-sm text-leaf-100/80 leading-relaxed">İhtiyaçlarınıza uygun çeşitli hazır kutular sunuyoruz. Ürünlerimiz kendi tarlalarımızdan ve yetiştirici dostlarımızdan hasat edilir; her kutu her hafta özenle seçilip paketlenir.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <span class="grid size-9 place-items-center rounded-full bg-white text-leaf-800 font-700 shrink-0">2</span>
                            <div>
                                <h3 class="font-700">Kapınıza ücretsiz teslimat</h3>
                                <p class="mt-1 text-sm text-leaf-100/80 leading-relaxed">Bölgeniz için haftalık teslimatınızı belirli bir günde alın. Siparişlerinizi kolayca yönetin, anlık bildirim ve teslimat güncellemeleri alın.</p>
                            </div>
                        </div>
                    </div>
                    <p class="mt-6 inline-flex items-center gap-2 rounded-lg bg-clay-500/90 px-4 py-2.5 text-sm font-600">
                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        Teslimat gününden 1 gün önce sipariş verin, %10 indirim kazanın.
                    </p>
                </div>

                <div class="bg-leaf-900/40 p-8 sm:p-12 lg:border-l border-leaf-700">
                    <h3 class="font-display text-xl font-700 mb-5">Teslimat Bölgeleri & Günleri</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between rounded-xl bg-white/10 px-4 py-3.5">
                            <span class="font-600">İstanbul Avrupa Yakası</span>
                            <span class="chip bg-leaf-500 text-white">Cumartesi</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-white/10 px-4 py-3.5">
                            <span class="font-600">İstanbul Anadolu Yakası</span>
                            <span class="chip bg-leaf-500 text-white">Çarşamba · Pazar</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-white/10 px-4 py-3.5">
                            <span class="font-600">Diğer Şehirler</span>
                            <span class="chip bg-white/20 text-white">Kargo ile</span>
                        </div>
                    </div>
                    <div class="mt-6 space-y-2.5 text-sm text-leaf-100/85">
                        <p class="flex items-start gap-2"><svg class="size-4 mt-0.5 text-leaf-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Her hafta kapınıza taze organik meyve ve sebze.</p>
                        <p class="flex items-start gap-2"><svg class="size-4 mt-0.5 text-leaf-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Ürünlerimizin tamamı yerel ve uluslararası sertifikalı.</p>
                        <p class="flex items-start gap-2"><svg class="size-4 mt-0.5 text-leaf-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Türkiye'nin en iyi organik üreticileriyle çalışıyoruz.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div id="mevsim">
        <x-product-section title="Mevsim Ürünleri" subtitle="Tam zamanı, en tazesi" :products="$seasonal" accent="leaf" />
    </div>
    <x-product-section title="Çok Satanlar" subtitle="Müşterilerin favorisi" :products="$bestsellers" accent="clay" />
    <x-product-section title="Yeni Ürünler" subtitle="Rafa yeni girenler" :products="$newest" accent="leaf" />
    <x-product-section title="Öne Çıkanlar" :products="$featured" accent="clay" />

    {{-- Üretici teaser --}}
    @if($producers->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12">
            <div class="rounded-3xl bg-paper p-8 sm:p-12">
                <div class="flex items-baseline justify-between gap-4">
                    <div class="max-w-2xl">
                        <h2 class="font-display text-xl sm:text-2xl font-700 tracking-tight text-bark">Üreticilerimiz</h2>
                        <p class="mt-1.5 text-sm text-bark/55">Her ürünün arkasında bir hikâye, bir çiftlik, bir emek var.</p>
                    </div>
                    <a href="{{ route('producers.index') }}" class="shrink-0 text-sm font-600 text-leaf-700 hover:text-leaf-800">Tümünü Gör <span aria-hidden="true">→</span></a>
                </div>
                <div class="mt-7 grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach($producers as $producer)
                        <a href="{{ route('producer.show', $producer->slug) }}" class="group rounded-2xl bg-white p-5 border border-paper card-lift">
                            <div class="size-14 rounded-full bg-leaf-100 overflow-hidden mb-3">
                                @if($producer->image)
                                    <img src="{{ asset('storage/' . $producer->image) }}" alt="{{ $producer->name }}" class="size-full object-cover">
                                @endif
                            </div>
                            <h3 class="font-600 text-bark group-hover:text-leaf-700">{{ $producer->name }}</h3>
                            @if($producer->location)<p class="text-xs text-bark/50 mt-0.5">{{ $producer->location }}</p>@endif
                            @if($producer->short_description)
                                <p class="mt-2 text-sm text-bark/60 line-clamp-3">{{ $producer->short_description }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Pratik ve Lezzetli Tarifler --}}
    @if($recipes->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12">
            <div class="mb-5 flex items-baseline justify-between gap-4 border-b border-paper pb-3">
                <div class="flex items-baseline gap-3 min-w-0">
                    <h2 class="font-display text-xl sm:text-2xl font-700 tracking-tight text-bark">Pratik ve Lezzetli Tarifler</h2>
                    <p class="hidden sm:block text-sm text-bark/45 truncate">Organik ürünlerle nefis tarifler</p>
                </div>
                <a href="{{ route('blog.index') }}" class="shrink-0 text-sm font-600 text-leaf-700 hover:text-leaf-800">Tümünü Gör <span aria-hidden="true">→</span></a>
            </div>
            <div class="grid sm:grid-cols-3 gap-6">
                @foreach($recipes as $recipe)
                    <a href="{{ route('blog.show', $recipe->slug) }}" class="group rounded-2xl border border-paper bg-white overflow-hidden card-lift">
                        <div class="aspect-[16/10] bg-paper overflow-hidden">
                            @if($recipe->cover_url)
                                <img src="{{ $recipe->cover_url }}" alt="{{ $recipe->title }}" loading="lazy" class="size-full object-cover group-hover:scale-105 transition duration-500">
                            @else
                                <div class="size-full grid place-items-center text-leaf-200">
                                    <svg class="size-12" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15.75a3 3 0 0 1-3 3h-9.75M21 15.75V8.25a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v7.5a3 3 0 0 0 3 3h.75m0 0a3 3 0 0 0 3 3m-3-3 .375-1.5"/></svg>
                                </div>
                            @endif
                        </div>
                        <div class="p-5">
                            <p class="text-[11px] font-600 uppercase tracking-wide text-clay-600">Tarif</p>
                            <h3 class="mt-1.5 font-display text-lg font-700 text-bark leading-snug line-clamp-2 group-hover:text-leaf-700">{{ $recipe->title }}</h3>
                            @if($recipe->excerpt)<p class="mt-1.5 text-sm text-bark/60 line-clamp-2">{{ $recipe->excerpt }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- İçerik Merkezi: Videolar · Bilimsel Makaleler · Haberler --}}
    <section class="mx-auto max-w-7xl px-4 py-12">
        <div class="mb-6 flex items-baseline justify-between gap-4 border-b border-paper pb-3">
            <div class="flex items-baseline gap-3 min-w-0">
                <h2 class="font-display text-xl sm:text-2xl font-700 tracking-tight text-bark">İçerik Merkezi</h2>
                <p class="hidden sm:block text-sm text-bark/45 truncate">Organik dünyasına dair bilgi, bilim ve ilham</p>
            </div>
            <a href="{{ route('blog.index') }}" class="shrink-0 text-sm font-600 text-leaf-700 hover:text-leaf-800">Tümünü Gör <span aria-hidden="true">→</span></a>
        </div>

        @php
            $hub = [
                [
                    'url'     => route('blog.index', ['kategori' => 'videolar']),
                    'eyebrow' => 'Tarladan kareler',
                    'title'   => 'Organik Tarım Videoları',
                    'desc'    => 'Ekimden hasada, üretim sürecimizi kendi gözünüzle keşfedin.',
                    'cta'     => 'İzlemeye Başla',
                    'tone'    => 'dark',
                    'icon'    => 'M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347a1.125 1.125 0 0 1-1.667-.985V5.653Z',
                ],
                [
                    'url'     => route('blog.index', ['kategori' => 'bilimsel-makaleler']),
                    'eyebrow' => 'Kanıta dayalı',
                    'title'   => 'Bilimsel Makaleler',
                    'desc'    => 'Organik ürünlerin sağlık ve çevreye etkisine dair araştırmalar.',
                    'cta'     => 'Makaleleri Oku',
                    'tone'    => 'leaf',
                    'icon'    => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
                ],
                [
                    'url'     => route('blog.index', ['kategori' => 'haberler']),
                    'eyebrow' => 'Gündem',
                    'title'   => 'Organik Dünyasından Haberler',
                    'desc'    => 'Sektördeki gelişmeler, üretici hikâyeleri ve güncel duyurular.',
                    'cta'     => 'Haberlere Göz At',
                    'tone'    => 'clay',
                    'icon'    => 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z',
                ],
            ];
        @endphp

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($hub as $c)
                @php
                    $isDark = $c['tone'] === 'dark';
                    $isClay = $c['tone'] === 'clay';
                    $wrap   = $isDark ? 'bg-leaf-800 text-white' : 'bg-white border border-paper';
                    $badge  = $isDark ? 'bg-white/10 text-white ring-1 ring-white/15'
                              : ($isClay ? 'bg-clay-100 text-clay-600' : 'bg-leaf-50 text-leaf-600');
                    $eye    = $isDark ? 'text-leaf-300' : ($isClay ? 'text-clay-600' : 'text-leaf-600');
                    $head   = $isDark ? 'text-white' : 'text-bark';
                    $body   = $isDark ? 'text-leaf-100/75' : 'text-bark/60';
                    $link   = $isDark ? 'text-white' : ($isClay ? 'text-clay-700' : 'text-leaf-700');
                    $deco   = $isDark ? 'text-white/[0.06]' : ($isClay ? 'text-clay-500/10' : 'text-leaf-600/10');
                @endphp
                <a href="{{ $c['url'] }}"
                   class="group relative overflow-hidden rounded-2xl p-6 sm:p-7 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_16px_34px_-12px_rgb(28_35_30/0.22)] {{ $wrap }}">
                    {{-- Arka plan dekor ikonu --}}
                    <svg class="absolute -right-6 -bottom-7 size-36 {{ $deco }} transition-transform duration-500 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $c['icon'] }}"/></svg>

                    <span class="relative grid size-12 place-items-center rounded-xl {{ $badge }}">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $c['icon'] }}"/></svg>
                    </span>

                    <p class="relative mt-5 text-[11px] font-700 uppercase tracking-[0.1em] {{ $eye }}">{{ $c['eyebrow'] }}</p>
                    <h3 class="relative mt-1.5 font-display text-lg font-700 leading-snug {{ $head }}">{{ $c['title'] }}</h3>
                    <p class="relative mt-1.5 text-sm leading-relaxed {{ $body }}">{{ $c['desc'] }}</p>

                    <span class="relative mt-5 inline-flex items-center gap-1.5 text-sm font-700 {{ $link }}">
                        {{ $c['cta'] }}
                        <svg class="size-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Sıkça Sorulan Sorular --}}
    <section class="mx-auto max-w-3xl px-4 py-12">
        <div class="mb-6 border-b border-paper pb-3">
            <h2 class="font-display text-xl sm:text-2xl font-700 tracking-tight text-bark">Sıkça Sorulan Sorular</h2>
        </div>
        <div class="space-y-3" x-data="{ open: 0 }">
            @foreach([
                ['Ürünleriniz gerçekten organik mi?', 'Tüm organik ürünlerimiz akredite kuruluşlardan sertifikalıdır; pestisit analiz raporları ve sertifika numaraları ürün sayfalarında paylaşılır. Şeffaflık önceliğimizdir.'],
                ['Teslimat ne kadar sürer?', 'Siparişiniz seçtiğiniz teslimat gününe göre hazırlanır. Taze ürünlerde soğuk zincir korunur. Kargo durumunu Hesabım > Siparişlerim üzerinden takip edebilirsiniz.'],
                ['Kargo ücreti ne kadar?', number_format(app(\App\Settings\GeneralSettings::class)->free_shipping_threshold,0,",",".") . ' TL ve üzeri siparişlerde kargo ücretsizdir. Bu tutarın altındaki siparişlerde ' . number_format(app(\App\Settings\CheckoutSettings::class)->shipping_cost,0,",",".") . ' TL kargo ücreti uygulanır.'],
                ['İade ve cayma hakkım var mı?', 'Ürünlerimiz çabuk bozulabilen gıda niteliğinde olduğundan, mevzuat gereği bu ürünlerde cayma hakkı/iade kabul edilmez. Yalnızca ayıplı, hasarlı veya yanlış gelen ürünlerde yasal haklarınız saklıdır; ayrıntılar İptal & İade sayfamızda.'],
                ['Hangi ödeme yöntemlerini kabul ediyorsunuz?', 'Kredi/banka kartı (3D Secure ile iyzico ve PayTR) ve havale/EFT ile ödeme yapabilirsiniz.'],
            ] as $idx => [$q, $a])
                <div class="rounded-xl border border-paper bg-white overflow-hidden">
                    <button @click="open === {{ $idx }} ? open = null : open = {{ $idx }}" class="w-full flex items-center justify-between gap-3 px-5 py-4 text-left font-600 text-bark hover:bg-leaf-50/40">
                        <span>{{ $q }}</span>
                        <svg class="size-5 shrink-0 transition-transform text-leaf-600" :class="open === {{ $idx }} ? 'rotate-45' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    </button>
                    <div x-show="open === {{ $idx }}" x-collapse x-cloak>
                        <p class="px-5 pb-4 text-sm text-bark/65 leading-relaxed">{{ $a }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- E-bülten --}}
    <section class="mx-auto max-w-7xl px-4 pb-12">
        <div class="rounded-3xl bg-leaf-800 text-white p-8 sm:p-12 flex flex-col lg:flex-row items-center justify-between gap-6"
             x-data="{
                email: '', website: '', loading: false, done: false, error: '',
                async submit() {
                    this.error = '';
                    if (!this.email) { this.error = 'Lütfen e-posta adresinizi girin.'; return; }
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route('newsletter.store') }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email: this.email, website: this.website }),
                        });
                        const data = await res.json();
                        if (res.ok && data.ok) { this.done = true; this.$dispatch('toast', data.message); }
                        else { this.error = (data.errors?.email?.[0]) || 'Geçerli bir e-posta girin.'; }
                    } catch (e) { this.error = 'Bir sorun oluştu, tekrar deneyin.'; }
                    this.loading = false;
                }
             }">
            <div class="max-w-md text-center lg:text-left">
                <h2 class="font-display text-2xl sm:text-3xl font-600">Fırsatlardan ilk siz haberdar olun</h2>
                <p class="mt-2 text-leaf-100/80">Mevsim ürünleri ve kampanyalar için e-bültenimize katılın.</p>
            </div>

            <div class="w-full max-w-md">
                {{-- Başarı durumu --}}
                <div x-show="done" x-cloak class="flex items-center gap-3 rounded-2xl bg-white/10 px-5 py-4 ring-1 ring-white/15">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-leaf-500">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    </span>
                    <p class="text-sm font-500 text-leaf-50">Kaydınız alındı! Fırsatlardan ilk siz haberdar olacaksınız.</p>
                </div>

                {{-- Form --}}
                <form x-show="!done" @submit.prevent="submit()" class="flex flex-col sm:flex-row gap-2">
                    {{-- honeypot (gizli; botlar için) --}}
                    <input type="text" x-model="website" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                    <input type="email" required placeholder="E-posta adresiniz" x-model="email" :disabled="loading"
                           class="flex-1 rounded-full border-0 bg-white px-5 py-3 text-bark placeholder:text-bark/50 shadow-sm focus:outline-none focus:ring-2 focus:ring-clay-400 disabled:opacity-60">
                    <button type="submit" :disabled="loading"
                            class="btn-leaf bg-clay-500 hover:bg-clay-600 shrink-0 disabled:opacity-70">
                        <svg x-show="loading" x-cloak class="size-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/></svg>
                        <span x-text="loading ? 'Gönderiliyor' : 'Katıl'"></span>
                    </button>
                </form>
                <p x-show="error" x-cloak x-text="error" class="mt-2 text-sm text-clay-200"></p>
                <p x-show="!done" class="mt-2 text-xs text-leaf-100/60">E-postanızı asla paylaşmayız. İstediğiniz zaman çıkabilirsiniz.</p>
            </div>
        </div>
    </section>

@endsection
