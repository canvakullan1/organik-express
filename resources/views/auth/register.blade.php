@extends('layouts.storefront')

@section('title', 'Üye Ol — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@section('content')
<div class="mx-auto max-w-md px-4 py-12">
    <div class="rounded-2xl border border-paper bg-white p-7 sm:p-8 shadow-sm">
        <h1 class="font-display text-2xl font-700 text-bark text-center">Üye Ol</h1>
        <p class="mt-1 text-center text-sm text-bark/60">Hızlı alışveriş, sipariş takibi ve para puan için hesap oluştur.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-600 text-bark mb-1.5">Ad Soyad</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none focus:ring-2 focus:ring-leaf-100 @error('name') border-red-400 @enderror">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-600 text-bark mb-1.5">E-posta</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none focus:ring-2 focus:ring-leaf-100 @error('email') border-red-400 @enderror">
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-600 text-bark mb-1.5">Telefon <span class="font-400 text-bark/40">(opsiyonel)</span></label>
                <input type="tel" name="phone" value="{{ old('phone') }}"
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none focus:ring-2 focus:ring-leaf-100">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-600 text-bark mb-1.5">Şifre</label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none focus:ring-2 focus:ring-leaf-100 @error('password') border-red-400 @enderror">
                    @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-600 text-bark mb-1.5">Şifre (Tekrar)</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none focus:ring-2 focus:ring-leaf-100">
                </div>
            </div>
            <label class="flex items-start gap-2 text-sm text-bark/70">
                <input type="checkbox" name="terms" value="1" class="mt-0.5 rounded border-paper text-leaf-600 focus:ring-leaf-200 @error('terms') border-red-400 @enderror">
                <span><a href="{{ url('/sayfa/kullanim-kosullari') }}" target="_blank" class="text-leaf-700 hover:underline">Üyelik sözleşmesi</a> ve <a href="{{ url('/sayfa/kvkk-aydinlatma-metni') }}" target="_blank" class="text-leaf-700 hover:underline">KVKK aydınlatma metni</a>ni okudum, onaylıyorum.</span>
            </label>
            @error('terms')<p class="-mt-2 text-xs text-red-600">{{ $message }}</p>@enderror

            <button type="submit" class="btn-leaf w-full !rounded-lg">Hesap Oluştur</button>
        </form>

        <p class="mt-6 text-center text-sm text-bark/60">
            Zaten üye misin?
            <a href="{{ route('login') }}" class="font-600 text-leaf-700 hover:underline">Giriş yap</a>
        </p>
    </div>
</div>
@endsection
