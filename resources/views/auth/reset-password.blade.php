@extends('layouts.storefront')

@section('title', 'Şifre Sıfırla — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@section('content')
<div class="mx-auto max-w-md px-4 py-12">
    <div class="rounded-2xl border border-paper bg-white p-7 sm:p-8 shadow-sm">
        <h1 class="font-display text-2xl font-700 text-bark text-center">Yeni Şifre Belirle</h1>

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="block text-sm font-600 text-bark mb-1.5">E-posta</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required readonly
                       class="w-full rounded-lg border border-paper bg-paper/40 px-4 py-2.5 text-sm text-bark/70">
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-600 text-bark mb-1.5">Yeni Şifre</label>
                <input type="password" name="password" required autofocus
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none focus:ring-2 focus:ring-leaf-100 @error('password') border-red-400 @enderror">
                @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-600 text-bark mb-1.5">Yeni Şifre (Tekrar)</label>
                <input type="password" name="password_confirmation" required
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none focus:ring-2 focus:ring-leaf-100">
            </div>
            <button type="submit" class="btn-leaf w-full !rounded-lg">Şifreyi Güncelle</button>
        </form>
    </div>
</div>
@endsection
