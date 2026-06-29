@extends('layouts.storefront')

@section('title', 'Bilgilerim — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10">
    <a href="{{ route('account.index') }}" class="text-sm text-bark/50 hover:text-leaf-700">← Hesabım</a>
    <h1 class="font-display text-2xl font-700 text-bark mt-2 mb-1">Bilgilerim</h1>
    <p class="text-bark/55 text-sm mb-6">Ad, e-posta ve telefon bilgilerini güncelle; istersen şifreni değiştir.</p>

    <form method="POST" action="{{ route('account.profile.update') }}"
          class="rounded-2xl border border-paper bg-white p-6 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-600 mb-1.5">Ad Soyad</label>
            <input name="name" value="{{ old('name', $user->name) }}" required
                   class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-600 mb-1.5">E-posta</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                   class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            <p class="mt-1 text-xs text-bark/45">E-postanı değiştirirsen yeni adresine doğrulama bağlantısı gönderilir.</p>
        </div>

        <div>
            <label class="block text-sm font-600 mb-1.5">Telefon</label>
            <input name="phone" value="{{ old('phone', $user->phone) }}" placeholder="05XX XXX XX XX"
                   class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="pt-4 border-t border-paper">
            <h2 class="font-700 text-bark text-sm mb-1">Şifre Değiştir</h2>
            <p class="text-xs text-bark/45 mb-3">Şifreni değiştirmek istemiyorsan bu alanları boş bırak.</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-600 mb-1.5">Mevcut Şifre</label>
                    <input type="password" name="current_password" autocomplete="current-password"
                           class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
                    @error('current_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-600 mb-1.5">Yeni Şifre</label>
                        <input type="password" name="password" autocomplete="new-password"
                               class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
                        @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-600 mb-1.5">Yeni Şifre (Tekrar)</label>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                               class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-leaf w-full !rounded-full">Kaydet</button>
    </form>
</div>
@endsection
