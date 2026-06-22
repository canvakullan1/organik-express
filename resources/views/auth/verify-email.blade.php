@extends('layouts.storefront')

@section('title', 'E-posta Doğrulama — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@section('content')
<div class="mx-auto max-w-md px-4 py-12">
    <div class="rounded-2xl border border-paper bg-white p-7 sm:p-8 shadow-sm text-center">
        <span class="grid size-14 place-items-center rounded-full bg-leaf-100 text-leaf-700 mx-auto mb-4">
            <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
        </span>
        <h1 class="font-display text-2xl font-700 text-bark">E-postanı Doğrula</h1>
        <p class="mt-2 text-sm text-bark/60">
            <strong>{{ auth()->user()->email }}</strong> adresine bir doğrulama bağlantısı gönderdik.
            Hesabını etkinleştirmek ve alışverişe başlamak için e-postandaki bağlantıya tıkla.
        </p>

        @if(session('success'))
            <div class="mt-5 rounded-lg bg-leaf-50 border border-leaf-200 px-4 py-3 text-sm text-leaf-800">{{ session('success') }}</div>
        @endif

        <div class="mt-6 flex flex-col gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn-leaf w-full !rounded-lg">Doğrulama Mailini Tekrar Gönder</button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-bark/50 hover:text-bark">Çıkış yap</button>
            </form>
        </div>

        <p class="mt-6 text-xs text-bark/40">Mail gelmedi mi? Spam/gereksiz klasörünü kontrol et.</p>
    </div>
</div>
@endsection
