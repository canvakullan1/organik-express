<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', ($seo->meta_title ?: $general->site_name))</title>
    <meta name="description" content="@yield('meta_description', $seo->meta_description ?: $general->tagline)">

    {{-- Favicon (admin panelinden yüklenebilir) --}}
    @if($general->favicon)
        <link rel="icon" href="{{ asset('storage/' . $general->favicon) }}">
    @endif

    {{-- Canonical --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Sosyal paylaşım --}}
    <meta property="og:title" content="@yield('title', $general->site_name)">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $general->site_name }}">
    <meta property="og:image" content="@yield('og_image', $seo->og_image ? asset('storage/' . $seo->og_image) : ($general->logo ? asset('storage/' . $general->logo) : ''))">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Organization yapısal verisi (schema.org) --}}
    @php
        $orgSchema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $general->site_name,
            'url' => url('/'),
            'logo' => $general->logo ? asset('storage/' . $general->logo) : null,
            'description' => $general->tagline,
            'sameAs' => array_values(array_filter([$social->instagram, $social->facebook, $social->x, $social->youtube, $social->linkedin])),
        ]);
        if ($contact->phone || $contact->email) {
            $orgSchema['contactPoint'] = array_filter([
                '@type' => 'ContactPoint', 'contactType' => 'customer service',
                'telephone' => $contact->phone, 'email' => $contact->email,
            ]);
        }
    @endphp
    <script type="application/ld+json">{!! json_encode($orgSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @stack('schema')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Dinamik tema: admin'in seçtiği renk + fontlar.
         @vite'tan SONRA gelmeli ki Tailwind @theme varsayılanlarını ezebilsin. --}}
    @include('partials.theme-styles')
    @stack('head')

    {{-- Google Tag Manager / Analytics --}}
    @if($seo->gtm_id)
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $seo->gtm_id }}');</script>
    @endif
    @if($seo->google_analytics_id)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $seo->google_analytics_id }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $seo->google_analytics_id }}');</script>
    @endif
</head>
<body class="min-h-screen flex flex-col antialiased">
    @if($seo->gtm_id)
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $seo->gtm_id }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    {{-- Duyuru bandı (çoklu mesaj, "·" ile ayrılır; otomatik döner) --}}
    @if($theme->announcement_enabled && $theme->announcement_text)
        @php
            $annMsgs = [];
            foreach (explode('·', $theme->announcement_text) as $m) {
                $m = trim($m);
                if ($m !== '') { $annMsgs[] = $m; }
            }
        @endphp
        <div class="bg-leaf-800 text-leaf-50 text-[13px]"
             x-data="{ i: 0, n: {{ count($annMsgs) }} }"
             x-init="if (n > 1) setInterval(() => i = (i + 1) % n, 3500)">
            <div class="mx-auto max-w-7xl px-4 h-9 flex items-center justify-center gap-2 text-center overflow-hidden">
                <svg class="size-4 shrink-0 text-leaf-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                @foreach($annMsgs as $idx => $msg)
                    <span x-show="i === {{ $idx }}" x-transition.opacity.duration.500ms class="{{ $idx !== 0 ? 'hidden' : '' }}">{{ $msg }}</span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Header --}}
    <header x-data="{ mobileOpen: false, scrolled: false }"
            @scroll.window.passive="scrolled = window.scrollY > 8"
            :class="scrolled ? 'shadow-[0_2px_16px_rgb(28_35_30/0.08)]' : ''"
            class="sticky top-0 z-40 transition-shadow duration-300">
        {{-- Üst yardımcı bar --}}
        <div class="bg-cream border-b border-paper text-bark/70 text-xs hidden sm:block">
            <div class="mx-auto max-w-7xl px-4 h-9 flex items-center justify-between">
                <span class="flex items-center gap-1.5 font-500">
                    <svg class="size-3.5 text-leaf-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    {{ $general->tagline ?: 'Sözde değil, belgeli organik' }}
                </span>
                <div class="flex items-center gap-4">
                    @if($contact->phone)
                        <a href="tel:{{ preg_replace('/\s+/', '', $contact->phone) }}" class="flex items-center gap-1 hover:text-leaf-700 font-600">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                            {{ $contact->phone }}
                        </a>
                    @endif
                    <a href="{{ url('/sayfa/teslimat-dagitim') }}" class="hover:text-leaf-700">Teslimat Günleri</a>
                    @auth
                        <a href="{{ route('account.index') }}" class="hover:text-leaf-700 font-600">Hesabım</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">@csrf<button class="hover:text-leaf-700">Çıkış</button></form>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-leaf-700">Giriş</a>
                        <a href="{{ route('register') }}" class="hover:text-leaf-700 font-600">Üye Ol</a>
                    @endauth
                </div>
            </div>
        </div>

        {{-- Ana bar --}}
        <div class="bg-white border-b border-paper">
            <div class="mx-auto max-w-7xl px-4">
                <div class="flex items-center gap-4 lg:gap-8 py-3">
                    {{-- Logo --}}
                    <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                        @if($general->logo)
                            <img src="{{ asset('storage/' . $general->logo) }}" alt="{{ $general->site_name }}" class="h-10 w-auto">
                        @else
                            <span class="grid size-10 place-items-center rounded-xl bg-leaf-600 text-white">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 0 0 0-18m0 18a9 9 0 0 1 0-18m0 18c2.5-2 4-5.5 4-9s-1.5-7-4-9m0 18c-2.5-2-4-5.5-4-9s1.5-7 4-9"/></svg>
                            </span>
                            <span class="font-display text-2xl font-700 text-leaf-800 leading-none tracking-tight">{{ $general->site_name }}</span>
                        @endif
                    </a>

                    {{-- Arama (büyük, ortada) --}}
                    <div class="flex-1 hidden md:block" x-data="searchBox()">
                        <form action="{{ route('search.index') }}" method="GET" class="relative flex">
                            <input
                                type="search" name="q" autocomplete="off"
                                x-model="q" @input.debounce.250ms="fetchSuggestions()" @focus="open = true" @click.away="open = false"
                                placeholder="Aradığınız ürün, kategori veya markayı yazın…"
                                class="w-full rounded-l-lg border-2 border-r-0 border-leaf-500 bg-white py-2.5 pl-4 pr-4 text-sm
                                       focus:outline-none placeholder:text-bark/40">
                            <button type="submit" class="rounded-r-lg bg-leaf-600 px-5 text-white hover:bg-leaf-700 transition" aria-label="Ara">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                            </button>

                            {{-- Otomatik tamamlama --}}
                            <div x-show="open && results.length" x-transition x-cloak
                                 class="absolute top-full mt-1 w-full rounded-xl border border-paper bg-white shadow-xl overflow-hidden z-50">
                                <template x-for="item in results" :key="item.url">
                                    <a :href="item.url" class="flex items-center gap-3 px-4 py-2.5 hover:bg-leaf-50">
                                        <div class="size-10 rounded-lg bg-paper bg-cover bg-center shrink-0" :style="item.image ? `background-image:url(${item.image})` : ''"></div>
                                        <span class="text-sm flex-1" x-text="item.name"></span>
                                        <span class="text-sm font-600 text-leaf-700 tnum" x-show="item.price" x-text="formatPrice(item.price)"></span>
                                    </a>
                                </template>
                            </div>
                        </form>
                    </div>

                    {{-- Aksiyonlar --}}
                    <nav class="flex items-center gap-1 shrink-0">
                        <a href="{{ auth()->check() ? route('account.index') : route('login') }}" class="hidden lg:flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-leaf-50">
                            <svg class="size-6 text-bark/70" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                            <span class="text-xs leading-tight text-bark/70">
                                @auth
                                    <span class="block">Hesabım</span><span class="block font-600 text-bark max-w-[110px] truncate">{{ \Illuminate\Support\Str::words(auth()->user()->name, 1, '') }}</span>
                                @else
                                    <span class="block">Giriş Yap</span><span class="block font-600 text-bark">Üye Ol</span>
                                @endauth
                            </span>
                        </a>
                        <a href="{{ route('wishlist.index') }}" class="relative grid size-11 place-items-center rounded-lg hover:bg-leaf-50" aria-label="Favoriler">
                            <svg class="size-6 text-bark/70" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                            @if($wishlistCount ?? 0)
                                <span class="absolute top-1 right-1 grid min-w-5 h-5 place-items-center rounded-full bg-clay-500 px-1 text-[11px] font-bold text-white tnum">{{ $wishlistCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('cart.index') }}" class="relative grid size-11 place-items-center rounded-lg hover:bg-leaf-50" aria-label="Sepet"
                           x-data="{ c: {{ (int) ($cartCount ?? 0) }} }" @cart-updated.window="c = $event.detail">
                            <svg class="size-6 text-bark/70" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                            <span x-show="c > 0" x-cloak x-text="c"
                                  class="absolute top-1 right-1 grid min-w-5 h-5 place-items-center rounded-full bg-leaf-600 px-1 text-[11px] font-bold text-white tnum"></span>
                        </a>
                        <button @click="mobileOpen = !mobileOpen" class="grid size-11 place-items-center rounded-lg hover:bg-leaf-50 lg:hidden" aria-label="Menü">
                            <svg class="size-6 text-bark" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        </button>
                    </nav>
                </div>
            </div>
        </div>

        {{-- Yeşil kategori barı (masaüstü) — premium mega menü --}}
        <div class="bg-leaf-700 text-white hidden lg:block shadow-sm">
            <div class="mx-auto max-w-7xl px-4">
                <nav class="flex items-center justify-center gap-1">
                    @if($headerMenu->isNotEmpty())
                        @foreach($headerMenu as $item)
                            <div class="relative"
                                 x-data="{ open: false, t: null }"
                                 @mouseenter="clearTimeout(t); open = true"
                                 @mouseleave="t = setTimeout(() => open = false, 140)">
                                <a href="{{ $item->resolved_url }}" @if($item->target_blank) target="_blank" @endif
                                   class="relative flex items-center gap-1 whitespace-nowrap px-4 py-[14px] text-[12.5px] font-700 uppercase tracking-[0.05em] text-white/90 hover:text-white transition-colors">
                                    {{ $item->label }}
                                    @if($item->children->isNotEmpty())
                                        <svg class="size-3 opacity-60 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                    @endif
                                    <span class="absolute inset-x-3 bottom-0 h-[2.5px] rounded-full bg-clay-400 origin-left transition-transform duration-300"
                                          :class="open ? 'scale-x-100' : 'scale-x-0'"></span>
                                </a>
                                @if($item->children->isNotEmpty())
                                    <div x-show="open" x-cloak
                                         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-1"
                                         class="absolute left-0 top-full pt-2.5 w-72 z-50">
                                        <div class="rounded-xl bg-white p-2.5 shadow-[0_18px_40px_-12px_rgb(28_35_30/0.25),0_4px_10px_rgb(28_35_30/0.06)] ring-1 ring-bark/5">
                                            @foreach($item->children as $child)
                                                <a href="{{ $child->resolved_url }}" @if($child->target_blank) target="_blank" @endif
                                                   class="group/link flex items-center justify-between rounded-lg px-3.5 py-2.5 text-[13.5px] font-500 text-bark/75 hover:bg-leaf-50 hover:text-leaf-800 transition-colors">
                                                    {{ $child->label }}
                                                    <svg class="size-3.5 text-leaf-600 opacity-0 -translate-x-1 transition-all duration-200 group-hover/link:opacity-100 group-hover/link:translate-x-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                                </a>
                                            @endforeach
                                            <div class="mt-1.5 border-t border-paper pt-1.5">
                                                <a href="{{ $item->resolved_url }}" class="flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-[13px] font-700 text-leaf-700 hover:bg-leaf-50">
                                                    Tümünü Gör
                                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        @foreach($menuCategories as $cat)
                            <div class="relative"
                                 x-data="{ open: false, t: null }"
                                 @mouseenter="clearTimeout(t); open = true"
                                 @mouseleave="t = setTimeout(() => open = false, 140)">
                                <a href="{{ route('category.show', $cat->slug) }}"
                                   class="relative flex items-center gap-1 whitespace-nowrap px-3 py-[14px] text-[12px] font-700 uppercase tracking-[0.05em] text-white/90 hover:text-white transition-colors">
                                    {{ $cat->name }}
                                    @if($cat->children->isNotEmpty())
                                        <svg class="size-3 opacity-60 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                    @endif
                                    <span class="absolute inset-x-3 bottom-0 h-[2.5px] rounded-full bg-clay-400 origin-left transition-transform duration-300"
                                          :class="open ? 'scale-x-100' : 'scale-x-0'"></span>
                                </a>
                                @if($cat->children->isNotEmpty())
                                    <div x-show="open" x-cloak
                                         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-1"
                                         class="absolute left-0 top-full pt-2.5 w-72 z-50">
                                        <div class="rounded-xl bg-white p-2.5 shadow-[0_18px_40px_-12px_rgb(28_35_30/0.25),0_4px_10px_rgb(28_35_30/0.06)] ring-1 ring-bark/5">
                                            @foreach($cat->children as $child)
                                                <a href="{{ route('category.show', $child->slug) }}"
                                                   class="group/link flex items-center justify-between rounded-lg px-3.5 py-2.5 text-[13.5px] font-500 text-bark/75 hover:bg-leaf-50 hover:text-leaf-800 transition-colors">
                                                    {{ $child->name }}
                                                    <svg class="size-3.5 text-leaf-600 opacity-0 -translate-x-1 transition-all duration-200 group-hover/link:opacity-100 group-hover/link:translate-x-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                                </a>
                                            @endforeach
                                            <div class="mt-1.5 border-t border-paper pt-1.5">
                                                <a href="{{ route('category.show', $cat->slug) }}" class="flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-[13px] font-700 text-leaf-700 hover:bg-leaf-50">
                                                    Tümünü Gör
                                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        {{-- Fallback'te Blog (özel menüde yer alıyorsa burada tekrar etmez) --}}
                        <a href="{{ route('blog.index') }}" class="relative group/nav whitespace-nowrap px-3 py-[14px] text-[12px] font-700 uppercase tracking-[0.05em] text-white/90 hover:text-white transition-colors">
                            Blog
                            <span class="absolute inset-x-2.5 bottom-0 h-[2.5px] rounded-full bg-clay-400 origin-left scale-x-0 transition-transform duration-300 group-hover/nav:scale-x-100"></span>
                        </a>
                    @endif

                    {{-- Sağ grup: vurgulu Kutular + ikincil bağlantılar --}}
                    <div class="ml-auto flex items-center gap-0.5">
                        <a href="{{ route('bundles.index') }}"
                           class="mr-1.5 inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg bg-clay-500 px-3.5 py-2 text-[12px] font-700 uppercase tracking-[0.05em] text-white shadow-sm transition hover:bg-clay-600 hover:shadow">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                            Kutular
                        </a>
                        <span class="h-5 w-px bg-white/20"></span>
                        @foreach([['certificates.index', 'Sertifikalar'], ['producers.index', 'Üreticiler']] as [$r, $label])
                            <a href="{{ route($r) }}" class="relative group/nav whitespace-nowrap px-2.5 py-[14px] text-[12px] font-700 uppercase tracking-[0.05em] text-white/75 hover:text-white transition-colors">
                                {{ $label }}
                                <span class="absolute inset-x-2 bottom-0 h-[2.5px] rounded-full bg-clay-400 origin-left scale-x-0 transition-transform duration-300 group-hover/nav:scale-x-100"></span>
                            </a>
                        @endforeach
                    </div>
                </nav>
            </div>
        </div>

        {{-- Mobil menü --}}
        <div x-show="mobileOpen" x-collapse x-cloak class="lg:hidden border-t border-paper bg-white">
            <div class="px-4 py-3 space-y-1 max-h-[70vh] overflow-y-auto">
                <form action="{{ route('search.index') }}" method="GET" class="relative mb-2">
                    <input type="search" name="q" placeholder="Ara…" class="w-full rounded-full border border-leaf-200 py-2 pl-10 pr-4 text-sm">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-leaf-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                </form>
                @foreach($menuCategories as $cat)
                    <details class="group">
                        <summary class="flex items-center justify-between rounded-lg px-3 py-2 font-600 cursor-pointer hover:bg-leaf-50">
                            <a href="{{ route('category.show', $cat->slug) }}">{{ $cat->name }}</a>
                            @if($cat->children->isNotEmpty())<svg class="size-4 group-open:rotate-180 transition" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>@endif
                        </summary>
                        @if($cat->children->isNotEmpty())
                            <div class="pl-4 py-1 space-y-0.5">
                                @foreach($cat->children as $child)
                                    <a href="{{ route('category.show', $child->slug) }}" class="block rounded-lg px-3 py-1.5 text-sm text-bark/80 hover:bg-leaf-50">{{ $child->name }}</a>
                                @endforeach
                            </div>
                        @endif
                    </details>
                @endforeach
                <a href="{{ route('blog.index') }}" class="block rounded-lg px-3 py-2 font-600 hover:bg-leaf-50">Blog</a>
                <a href="{{ route('wishlist.index') }}" class="block rounded-lg px-3 py-2 font-600 hover:bg-leaf-50">Favorilerim</a>
                @auth
                    <a href="{{ route('account.index') }}" class="block rounded-lg px-3 py-2 font-600 hover:bg-leaf-50">Hesabım</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="block w-full text-left rounded-lg px-3 py-2 font-600 hover:bg-leaf-50">Çıkış Yap</button></form>
                @else
                    <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 font-600 hover:bg-leaf-50">Giriş Yap</a>
                    <a href="{{ route('register') }}" class="block rounded-lg px-3 py-2 font-600 text-leaf-700 hover:bg-leaf-50">Üye Ol</a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Bildirimler: sunucu flash + canlı toast (sepete ekleme vb.) --}}
    <div x-data="{ show: false, msg: '', t: null,
                   pop(m) { this.msg = m; this.show = true; clearTimeout(this.t); this.t = setTimeout(() => this.show = false, 3200); } }"
         @toast.window="pop($event.detail)"
         @if(session('success')) x-init="pop(@js(session('success')))" @endif
         class="fixed bottom-6 right-6 z-50">
        <div x-show="show" x-cloak
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-2"
             class="flex items-center gap-2.5 rounded-xl bg-bark px-4 py-3 text-sm font-600 text-white shadow-xl">
            <span class="grid size-5 place-items-center rounded-full bg-leaf-500 shrink-0">
                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            </span>
            <span x-text="msg"></span>
        </div>
    </div>

    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="mt-20 bg-leaf-900 text-leaf-100">
        {{-- Güven şeritleri --}}
        <div class="border-b border-leaf-800">
            <div class="mx-auto max-w-7xl px-4 py-8 grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach([
                    ['M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'Sertifikalı Ürünler', 'Organik sertifika & analiz'],
                    ['M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12', 'Ücretsiz Kargo', '750 TL üzeri'],
                    ['M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Z', 'Güvenli Ödeme', '3D Secure · iyzico/PayTR'],
                    ['M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z', 'Taze Teslimat', 'Soğuk zincir & teslim günü'],
                ] as [$icon, $title, $sub])
                    <div class="flex items-center gap-3">
                        <span class="grid size-11 place-items-center rounded-full bg-leaf-800 text-leaf-200 shrink-0">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        </span>
                        <div>
                            <p class="font-600 text-white text-sm">{{ $title }}</p>
                            <p class="text-xs text-leaf-300">{{ $sub }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-12 grid grid-cols-2 md:grid-cols-4 gap-8 text-sm">
            <div class="col-span-2 md:col-span-1">
                @if($general->logo)
                    <img src="{{ asset('storage/' . $general->logo) }}" alt="{{ $general->site_name }}" class="h-9 w-auto brightness-0 invert opacity-90">
                @else
                    <span class="font-display text-2xl font-600 text-white">{{ $general->site_name }}<span class="text-clay-400">.</span></span>
                @endif
                @if($general->footer_about)
                    <p class="mt-3 text-leaf-300 leading-relaxed">{{ $general->footer_about }}</p>
                @endif

                {{-- Sosyal medya --}}
                @php
                    $socialLinks = array_filter([
                        'instagram' => $social->instagram, 'facebook' => $social->facebook,
                        'x' => $social->x, 'youtube' => $social->youtube,
                        'linkedin' => $social->linkedin, 'tiktok' => $social->tiktok,
                    ]);
                @endphp
                @if(! empty($socialLinks))
                    <div class="mt-4 flex gap-2">
                        @foreach($socialLinks as $platform => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($platform) }}"
                               class="grid size-9 place-items-center rounded-full bg-leaf-800 text-leaf-200 hover:bg-leaf-700 hover:text-white transition">
                                <span class="text-xs font-bold uppercase">{{ substr($platform, 0, 1) }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Footer menü sütunları: "Menü Yönetimi"nden (footer) düzenlenir.
                 Tanımlı değilse sayfa gruplarına geri düşer. --}}
            @if($footerMenu->isNotEmpty())
                @foreach($footerMenu as $column)
                    <div>
                        <h4 class="font-600 text-white mb-3">{{ $column->label }}</h4>
                        @if($column->children->isNotEmpty())
                            <ul class="space-y-2 text-leaf-300">
                                @foreach($column->children as $link)
                                    <li><a href="{{ $link->resolved_url }}" @if($link->target_blank) target="_blank" @endif class="hover:text-white">{{ $link->label }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            @else
                @foreach(\App\Models\Page::FOOTER_GROUPS as $groupKey => $groupLabel)
                    @if(($footerPages[$groupKey] ?? collect())->isNotEmpty())
                        <div>
                            <h4 class="font-600 text-white mb-3">{{ $groupLabel }}</h4>
                            <ul class="space-y-2 text-leaf-300">
                                @foreach($footerPages[$groupKey] as $page)
                                    <li><a href="{{ route('page.show', $page->slug) }}" class="hover:text-white">{{ $page->title }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>

        {{-- İletişim şeridi --}}
        @if($contact->phone || $contact->email || $contact->address)
            <div class="border-t border-leaf-800">
                <div class="mx-auto max-w-7xl px-4 py-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-leaf-300">
                    @if($contact->phone)<span class="flex items-center gap-1.5"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>{{ $contact->phone }}</span>@endif
                    @if($contact->email)<span class="flex items-center gap-1.5"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>{{ $contact->email }}</span>@endif
                    @if($contact->working_hours)<span class="text-leaf-400">{{ $contact->working_hours }}</span>@endif
                </div>
            </div>
        @endif

        {{-- Ödeme yöntemleri --}}
        <div class="border-t border-leaf-800">
            <div class="mx-auto max-w-7xl px-4 py-4 flex flex-wrap items-center justify-between gap-3">
                <span class="text-xs text-leaf-400 flex items-center gap-1.5">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Z"/></svg>
                    256-bit SSL ile güvenli ödeme
                </span>
                <div class="flex flex-wrap items-center gap-1.5">
                    @foreach(['Visa', 'Mastercard', 'Troy', 'iyzico', 'PayTR', 'Havale/EFT'] as $pm)
                        <span class="rounded-md bg-white/95 px-2.5 py-1 text-[11px] font-700 tracking-wide text-leaf-900">{{ $pm }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="border-t border-leaf-800">
            <div class="mx-auto max-w-7xl px-4 py-5 text-xs text-leaf-400 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span>© {{ date('Y') }} {{ $general->site_name }}. Tüm hakları saklıdır.</span>
                <span class="flex items-center gap-3">
                    <a href="{{ route('producers.index') }}" class="hover:text-white">Üreticiler</a>
                    @if($seo->etbis_url)
                        <a href="{{ $seo->etbis_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 rounded-full border border-leaf-700 px-2.5 py-1 hover:text-white hover:border-leaf-500">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            ETBİS Doğrula
                        </a>
                    @else
                        <span>ETBİS kayıtlıdır · Sertifikalı organik gıda</span>
                    @endif
                </span>
            </div>
        </div>
    </footer>

    {{-- Çerez bandı (KVKK) --}}
    <div x-data="{ show: ! localStorage.getItem('cookie_consent') }" x-show="show" x-cloak x-transition
         class="fixed inset-x-0 bottom-0 z-50 p-4">
        <div class="mx-auto max-w-4xl rounded-2xl bg-bark text-white shadow-2xl p-5 flex flex-col sm:flex-row items-center gap-4">
            <p class="text-sm text-white/80 flex-1">
                Deneyiminizi iyileştirmek için çerezler kullanıyoruz. Detaylar için
                <a href="{{ url('/sayfa/gizlilik-guvenlik-politikasi') }}" class="text-leaf-300 underline hover:text-leaf-200">Gizlilik Politikası</a> ve
                <a href="{{ url('/sayfa/kvkk-aydinlatma-metni') }}" class="text-leaf-300 underline hover:text-leaf-200">KVKK Aydınlatma Metni</a>'ni inceleyebilirsiniz.
            </p>
            <div class="flex gap-2 shrink-0">
                <button @click="localStorage.setItem('cookie_consent','rejected'); show=false" class="rounded-full border border-white/25 px-4 py-2 text-sm hover:bg-white/10">Reddet</button>
                <button @click="localStorage.setItem('cookie_consent','accepted'); show=false" class="rounded-full bg-leaf-500 px-5 py-2 text-sm font-600 hover:bg-leaf-400">Kabul Et</button>
            </div>
        </div>
    </div>

    <script>
        function searchBox() {
            return {
                q: '', open: false, results: [],
                fetchSuggestions() {
                    if (this.q.trim().length < 2) { this.results = []; return; }
                    fetch(`{{ route('search.suggest') }}?q=${encodeURIComponent(this.q)}`)
                        .then(r => r.json()).then(d => { this.results = d; this.open = true; });
                },
                formatPrice(v) {
                    return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(v);
                },
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
