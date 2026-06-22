@extends('layouts.storefront')

@section('title', 'Şifremi Unuttum — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@section('content')
<div class="mx-auto max-w-md px-4 py-12">
    <div class="rounded-2xl border border-paper bg-white p-7 sm:p-8 shadow-sm">
        <h1 class="font-display text-2xl font-700 text-bark text-center">Şifremi Unuttum</h1>
        <p class="mt-1 text-center text-sm text-bark/60">E-postanı gir, şifre sıfırlama bağlantısı gönderelim.</p>

        @if(session('success'))
            <div class="mt-5 rounded-lg bg-leaf-50 border border-leaf-200 px-4 py-3 text-sm text-leaf-800">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-600 text-bark mb-1.5">E-posta</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none focus:ring-2 focus:ring-leaf-100 @error('email') border-red-400 @enderror">
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-leaf w-full !rounded-lg">Sıfırlama Bağlantısı Gönder</button>
        </form>

        <p class="mt-6 text-center text-sm text-bark/60">
            <a href="{{ route('login') }}" class="font-600 text-leaf-700 hover:underline">← Girişe dön</a>
        </p>
    </div>
</div>
@endsection
