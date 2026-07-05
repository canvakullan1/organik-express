@extends('layouts.storefront')

@section('title', 'Sertifikalar & Belgeler — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@section('meta_description', 'Organik tarım, ECOCERT ve ISO standartları ile üretici ve tedarikçilerimizin resmî sertifikaları. Belgeli, izlenebilir ve şeffaf organik gıda.')

@section('content')
@php
    $standards = $certificates->where('group', 'standart');
    $docs = $certificates->where('group', 'tedarikci');
    $byHolder = $docs->groupBy('label');

    // Belge sahibi işletmelerin künyeleri
    $holderMeta = [
        'Elta-Ada Organik Çiftlik' => ['mono' => 'EA', 'tag' => 'Üreticimiz', 'loc' => 'Gökçeada · Çanakkale', 'desc' => "Türkiye'nin ilk organik pastörize süt üreticisi; ECOCERT sertifikalı, AB onaylı süt çiftliği."],
        'Orvital Organik Gıda' => ['mono' => 'OR', 'tag' => 'Tedarikçimiz', 'loc' => 'İstanbul', 'desc' => 'Organik et, tavuk ve gıda ürünlerinde ECOCERT sertifikalı işleme ve dağıtım.'],
        'Sade Organik' => ['mono' => 'SD', 'tag' => 'Tedarikçimiz', 'loc' => 'İstanbul', 'desc' => 'ECOCERT sertifikalı organik gıda; ISO 9001, ISO 14001 ve ISO 10002 yönetim sistemleri.'],
    ];

    // Standart kartı ikonları (heroicons)
    $iconPaths = [
        'Organik' => 'M12 21a9 9 0 0 0 0-18m0 18a9 9 0 0 1 0-18m0 18c2.5-2 4-5.5 4-9s-1.5-7-4-9m0 18c-2.5-2-4-5.5-4-9s1.5-7 4-9',
        'ECOCERT' => 'm20.893 13.393-1.135-1.135a2.252 2.252 0 0 1-.421-.585l-1.08-2.16a.414.414 0 0 0-.663-.107.827.827 0 0 1-.812.21l-1.273-.363a.89.89 0 0 0-.738 1.595l.587.39c.59.395.674 1.23.172 1.732l-.2.2c-.212.212-.33.498-.33.796v.41c0 .409-.11.809-.32 1.158l-1.315 2.191a2.11 2.11 0 0 1-1.81 1.025 1.055 1.055 0 0 1-1.055-1.055v-1.172c0-.92-.56-1.747-1.414-2.089l-.655-.261a2.25 2.25 0 0 1-1.383-2.46l.007-.042a2.25 2.25 0 0 1 .29-.787l.09-.15a2.25 2.25 0 0 1 2.37-1.048l1.178.236a1.125 1.125 0 0 0 1.302-.795l.208-.73a1.125 1.125 0 0 0-.578-1.315l-.665-.332-.091.091a2.25 2.25 0 0 1-1.591.659h-.18c-.249 0-.487.1-.662.274a.931.931 0 0 1-1.458-1.137l1.411-2.353a2.25 2.25 0 0 0 .286-.76m11.928 9.869A9 9 0 0 0 8.965 3.525m11.928 9.868A9 9 0 1 1 8.965 3.525',
        'ISO 22000' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
        'ISO 9001' => 'M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z',
        'ISO 14001' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
        'Helal' => 'M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z',
    ];
    $fallbackIcon = 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z';
@endphp

<div x-data="{ lb: null }">

    {{-- ══ Hero ══ --}}
    <section class="relative overflow-hidden bg-leaf-900 text-white">
        <div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle at 1px 1px, #fff 1.2px, transparent 0); background-size: 24px 24px;"></div>
        <div class="absolute -top-28 -right-28 size-96 rounded-full bg-leaf-600/30 blur-3xl"></div>
        <div class="absolute -bottom-36 -left-20 size-80 rounded-full bg-clay-500/20 blur-3xl"></div>

        <div class="relative mx-auto max-w-5xl px-4 py-16 sm:py-20 text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-[11px] font-700 uppercase tracking-[0.14em] text-leaf-100">
                <svg class="size-3.5 text-clay-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Şeffaflık &amp; Güvence
            </span>
            <h1 class="mt-5 font-display text-4xl sm:text-5xl font-700 leading-tight">Sertifikalar &amp; Belgeler</h1>
            <p class="mx-auto mt-4 max-w-2xl text-[15px] leading-relaxed text-leaf-100/80">
                Raflarımızdaki her ürünün arkasında denetlenen bir üretim zinciri var. Ürünlerimizin taşıdığı standartları ve birlikte çalıştığımız işletmelerin resmî belgelerini aşağıda inceleyebilirsiniz.
            </p>

            <div class="mx-auto mt-10 grid max-w-2xl grid-cols-3 divide-x divide-white/10 rounded-2xl border border-white/10 bg-white/5 backdrop-blur-sm">
                <div class="px-2 py-5">
                    <p class="font-display text-3xl font-700 tnum">{{ $docs->count() }}</p>
                    <p class="mt-1 text-[11px] font-600 uppercase tracking-wider text-leaf-200/80">Resmî Belge</p>
                </div>
                <div class="px-2 py-5">
                    <p class="font-display text-3xl font-700 tnum">{{ $byHolder->count() }}</p>
                    <p class="mt-1 text-[11px] font-600 uppercase tracking-wider text-leaf-200/80">Sertifikalı İşletme</p>
                </div>
                <div class="px-2 py-5">
                    <p class="font-display text-3xl font-700 tnum">{{ $standards->count() }}</p>
                    <p class="mt-1 text-[11px] font-600 uppercase tracking-wider text-leaf-200/80">Uluslararası Standart</p>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4">

        {{-- ══ Standartlar ══ --}}
        @if($standards->isNotEmpty())
            <section class="py-16">
                <div class="mb-10 text-center">
                    <p class="text-[11px] font-700 uppercase tracking-[0.18em] text-clay-600">Güvence Çerçevemiz</p>
                    <h2 class="mt-2 font-display text-3xl font-700 text-bark">Ürünlerimizin Taşıdığı Standartlar</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm text-bark/55">Tedarik zincirimizdeki her işletme, bu ulusal ve uluslararası standartlara göre üretir ve bağımsız kuruluşlarca denetlenir.</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($standards as $c)
                        <div class="group relative rounded-2xl border border-bark/10 bg-white p-6 shadow-[0_1px_2px_rgb(28_35_30/0.04)] transition duration-300 hover:-translate-y-1 hover:border-leaf-200 hover:shadow-[0_16px_40px_-16px_rgb(28_35_30/0.18)]">
                            <div class="flex items-center gap-4">
                                <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-leaf-50 to-leaf-100/60 text-leaf-700 ring-1 ring-leaf-100 transition group-hover:from-leaf-600 group-hover:to-leaf-700 group-hover:text-white">
                                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPaths[$c->label] ?? $fallbackIcon }}"/></svg>
                                </span>
                                <div>
                                    <p class="text-[10.5px] font-700 uppercase tracking-[0.12em] text-leaf-600/70">{{ $c->label }}</p>
                                    <h3 class="font-display text-[17px] font-700 leading-snug text-bark">{{ $c->name }}</h3>
                                </div>
                            </div>
                            @if($c->description)
                                <p class="mt-4 text-[13.5px] leading-relaxed text-bark/60">{{ $c->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ══ İşletme belgeleri ══ --}}
        @if($byHolder->isNotEmpty())
            <section class="pb-16">
                <div class="mb-10 text-center">
                    <p class="text-[11px] font-700 uppercase tracking-[0.18em] text-clay-600">Belgeli Tedarik Zinciri</p>
                    <h2 class="mt-2 font-display text-3xl font-700 text-bark">Üretici ve Tedarikçilerimizin Belgeleri</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm text-bark/55">Belgeler ilgili işletmelere aittir ve bilgileri dahilinde paylaşılmıştır. İncelemek için belgeye tıklayın.</p>
                </div>

                <div class="space-y-10">
                    @foreach($byHolder as $holder => $certs)
                        @php $meta = $holderMeta[$holder] ?? ['mono' => mb_substr($holder, 0, 2), 'tag' => 'Tedarikçimiz', 'loc' => '', 'desc' => '']; @endphp
                        <article class="overflow-hidden rounded-3xl border border-bark/10 bg-white shadow-[0_1px_3px_rgb(28_35_30/0.05)]">
                            {{-- İşletme başlığı --}}
                            <header class="flex flex-wrap items-center gap-4 border-b border-bark/5 bg-gradient-to-r from-leaf-50/70 via-white to-white px-6 py-5 sm:px-8">
                                <span class="grid size-12 sm:size-14 shrink-0 place-items-center rounded-2xl bg-leaf-800 font-display text-lg font-700 text-white shadow-inner">{{ $meta['mono'] }}</span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-display text-xl font-700 text-bark">{{ $holder }}</h3>
                                        <span class="rounded-full bg-clay-50 px-2.5 py-0.5 text-[11px] font-700 text-clay-700 ring-1 ring-clay-100">{{ $meta['tag'] }}</span>
                                    </div>
                                    <p class="mt-0.5 truncate text-[13px] text-bark/55">
                                        @if($meta['loc'])<span class="font-600 text-bark/70">{{ $meta['loc'] }}</span> · @endif{{ $meta['desc'] }}
                                    </p>
                                </div>
                                <span class="rounded-full border border-bark/10 px-3 py-1 text-xs font-600 text-bark/50 tnum">{{ $certs->count() }} belge</span>
                            </header>

                            {{-- Belgeler --}}
                            <div class="grid grid-cols-2 gap-4 p-6 sm:grid-cols-3 sm:p-8 lg:grid-cols-4">
                                @foreach($certs as $c)
                                    <figure class="group/doc">
                                        <button type="button"
                                                @click="lb = { src: '{{ asset('storage/' . $c->image) }}', title: @js($c->name), holder: @js($holder) }"
                                                class="relative block w-full overflow-hidden rounded-xl border border-bark/10 bg-gradient-to-b from-white to-paper/60 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-leaf-300 hover:shadow-[0_14px_30px_-12px_rgb(28_35_30/0.25)] focus:outline-none focus-visible:ring-2 focus-visible:ring-leaf-500">
                                            <span class="block aspect-[3/4] overflow-hidden">
                                                @if($c->image)
                                                    <img src="{{ asset('storage/' . $c->image) }}" alt="{{ $c->name }} — {{ $holder }}" loading="lazy"
                                                         class="size-full object-contain p-2 transition duration-500 group-hover/doc:scale-[1.04]">
                                                @endif
                                            </span>
                                            {{-- büyüteç --}}
                                            <span class="absolute inset-0 grid place-items-center bg-bark/0 transition group-hover/doc:bg-bark/25" aria-hidden="true">
                                                <span class="grid size-11 scale-75 place-items-center rounded-full bg-white/95 text-bark opacity-0 shadow-lg transition duration-300 group-hover/doc:scale-100 group-hover/doc:opacity-100">
                                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM10.5 7.5v6m3-3h-6"/></svg>
                                                </span>
                                            </span>
                                        </button>
                                        <figcaption class="mt-2.5 px-0.5">
                                            <p class="text-[12.5px] font-600 leading-snug text-bark/80">{{ $c->name }}</p>
                                            <p class="mt-0.5 flex items-center gap-2 text-[11px] text-bark/45">
                                                @if($c->valid_until)<span>Geçerlilik: {{ $c->valid_until->format('m.Y') }}</span>@endif
                                                @if($c->file)
                                                    <a href="{{ asset('storage/' . $c->file) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-700 text-leaf-700 hover:underline">
                                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                                        PDF
                                                    </a>
                                                @endif
                                            </p>
                                        </figcaption>
                                    </figure>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ══ Doğrulama bandı ══ --}}
        <section class="pb-20">
            <div class="relative overflow-hidden rounded-3xl bg-leaf-900 px-6 py-10 text-white sm:px-12">
                <div class="absolute -right-16 -top-16 size-64 rounded-full bg-leaf-600/30 blur-3xl"></div>
                <div class="relative flex flex-col items-start gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="max-w-xl">
                        <h2 class="font-display text-2xl font-700">Belge doğrulama &amp; analiz talebi</h2>
                        <p class="mt-2 text-sm leading-relaxed text-leaf-100/75">Sertifikalar; ECOCERT, TÜRKAK ve ilgili bakanlık kayıtları üzerinden doğrulanabilir. Belirli bir ürünün sertifika veya analiz bilgisine ulaşmak isterseniz bize yazın, en kısa sürede paylaşalım.</p>
                    </div>
                    <a href="{{ url('/sayfa/iletisim') }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-700 text-leaf-900 shadow-lg transition hover:bg-leaf-50">
                        İletişime Geç
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </a>
                </div>
            </div>
        </section>
    </div>

    {{-- ══ Lightbox (aynı sayfada büyütme) ══ --}}
    <div x-show="lb" x-cloak
         @keydown.escape.window="lb = null"
         class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-8"
         role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-bark/85 backdrop-blur-sm" @click="lb = null"
             x-show="lb" x-transition.opacity.duration.200ms></div>

        <div x-show="lb" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="relative flex max-h-full w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            {{-- Başlık + kapat --}}
            <div class="flex items-center justify-between gap-4 border-b border-bark/10 px-5 py-3.5">
                <div class="min-w-0">
                    <p class="truncate text-sm font-700 text-bark" x-text="lb?.title"></p>
                    <p class="truncate text-xs text-bark/50" x-text="lb?.holder"></p>
                </div>
                <button type="button" @click="lb = null" aria-label="Kapat"
                        class="grid size-10 shrink-0 place-items-center rounded-xl text-bark/60 transition hover:bg-bark/5 hover:text-bark focus:outline-none focus-visible:ring-2 focus-visible:ring-leaf-500">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            {{-- Belge --}}
            <div class="overflow-auto bg-bark/[0.03] p-3 sm:p-5">
                <img :src="lb?.src" :alt="lb?.title" class="mx-auto max-h-[74vh] w-auto rounded-lg shadow-md">
            </div>
        </div>
    </div>

</div>
@endsection
