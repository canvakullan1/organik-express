@extends('layouts.storefront')

@section('title', 'Hesabım — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10">
    {{-- Karşılama --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="font-display text-3xl font-700 text-bark">Merhaba, {{ $user->name }} 👋</h1>
            <p class="mt-1 text-bark/60">{{ $user->email }}
                @if($user->hasVerifiedEmail())
                    <span class="chip bg-leaf-50 text-leaf-700 ml-1">✓ Doğrulandı</span>
                @endif
            </p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-ghost !rounded-lg">Çıkış Yap</button>
        </form>
    </div>

    {{-- Kartlar --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @php
            $cards = [
                ['Siparişlerim', 'Geçmiş ve aktif siparişlerin', 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272', route('account.orders'), auth()->user()->orders()->count() . ' sipariş'],
                ['Adreslerim', 'Teslimat ve fatura adresleri', 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z', route('account.addresses'), auth()->user()->addresses()->count() . ' adres'],
                ['Favorilerim', 'Beğendiğin ürünler', 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z', route('wishlist.index'), ($wishlistCount ?? 0) . ' ürün'],
                ['Para Puanım', 'Kazanım ve kullanım', 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', route('account.loyalty'), number_format($loyaltyBalance ?? 0, 0, ',', '.') . ' puan'],
                ['Bilgilerim', 'Ad, e-posta, şifre', 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z', null, 'Yakında'],
                ['İletişim İzinleri', 'E-bülten ve bildirim tercihleri', 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0', null, 'Yakında'],
            ];
        @endphp

        @foreach($cards as [$title, $desc, $icon, $url, $badge])
            <a @if($url) href="{{ $url }}" @endif
               class="group rounded-2xl border border-paper bg-white p-5 transition hover:shadow-md hover:border-leaf-200 @if(!$url) opacity-70 @endif">
                <div class="flex items-start justify-between">
                    <span class="grid size-11 place-items-center rounded-xl bg-leaf-50 text-leaf-700">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </span>
                    @if($badge)<span class="text-[11px] font-600 text-bark/40">{{ $badge }}</span>@endif
                </div>
                <h3 class="mt-3 font-700 text-bark group-hover:text-leaf-700">{{ $title }}</h3>
                <p class="mt-0.5 text-sm text-bark/55">{{ $desc }}</p>
            </a>
        @endforeach
    </div>
</div>
@endsection
