@extends('layouts.storefront')

@section('title', 'Giriş Yap — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@section('content')
<div class="mx-auto max-w-md px-4 py-12">
    <div class="rounded-2xl border border-paper bg-white p-7 sm:p-8 shadow-sm">
        <h1 class="font-display text-2xl font-700 text-bark text-center">Giriş Yap</h1>
        <p class="mt-1 text-center text-sm text-bark/60">Hesabına giriş yaparak alışverişe devam et.</p>

        @if(session('success'))
            <div class="mt-5 rounded-lg bg-leaf-50 border border-leaf-200 px-4 py-3 text-sm text-leaf-800">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-600 text-bark mb-1.5">E-posta</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none focus:ring-2 focus:ring-leaf-100 @error('email') border-red-400 @enderror">
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-600 text-bark">Şifre</label>
                    <a href="{{ route('password.request') }}" class="text-xs text-leaf-700 hover:underline">Şifremi unuttum</a>
                </div>
                <input type="password" name="password" required
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none focus:ring-2 focus:ring-leaf-100">
                @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-bark/70">
                <input type="checkbox" name="remember" class="rounded border-paper text-leaf-600 focus:ring-leaf-200">
                Beni hatırla
            </label>

            <button type="submit" class="btn-leaf w-full !rounded-lg">Giriş Yap</button>
        </form>

        <p class="mt-6 text-center text-sm text-bark/60">
            Hesabın yok mu?
            <a href="{{ route('register') }}" class="font-600 text-leaf-700 hover:underline">Üye ol</a>
        </p>
    </div>
</div>
@endsection
