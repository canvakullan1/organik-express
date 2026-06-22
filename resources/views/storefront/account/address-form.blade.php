@extends('layouts.storefront')

@section('title', ($address->exists ? 'Adresi Düzenle' : 'Yeni Adres') . ' — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10" x-data="{ corporate: {{ old('is_corporate', $address->is_corporate) ? 'true' : 'false' }} }">
    <a href="{{ route('account.addresses') }}" class="text-sm text-bark/50 hover:text-leaf-700">← Adreslerim</a>
    <h1 class="font-display text-2xl font-700 text-bark mt-2 mb-6">{{ $address->exists ? 'Adresi Düzenle' : 'Yeni Adres Ekle' }}</h1>

    <form method="POST" action="{{ $address->exists ? route('account.address.update', $address) : route('account.address.store') }}"
          class="rounded-2xl border border-paper bg-white p-6 space-y-4">
        @csrf
        @if($address->exists) @method('PUT') @endif
        @if(request('return')) <input type="hidden" name="return" value="{{ request('return') }}"> @endif

        <div>
            <label class="block text-sm font-600 mb-1.5">Adres Başlığı</label>
            <input name="title" value="{{ old('title', $address->title) }}" placeholder="ör. Ev, İş" required
                   class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
            @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_corporate" value="1" x-model="corporate" class="rounded border-paper text-leaf-600">
            Kurumsal (fatura için firma bilgileri)
        </label>

        <div x-show="!corporate" class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-600 mb-1.5">Ad</label>
                <input name="first_name" value="{{ old('first_name', $address->first_name) }}"
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
                @error('first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-600 mb-1.5">Soyad</label>
                <input name="last_name" value="{{ old('last_name', $address->last_name) }}"
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
                @error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div x-show="corporate" x-cloak class="space-y-4">
            <div>
                <label class="block text-sm font-600 mb-1.5">Firma Ünvanı</label>
                <input name="company_name" value="{{ old('company_name', $address->company_name) }}"
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
                @error('company_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-600 mb-1.5">Vergi Dairesi</label>
                    <input name="tax_office" value="{{ old('tax_office', $address->tax_office) }}"
                           class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-600 mb-1.5">Vergi No</label>
                    <input name="tax_number" value="{{ old('tax_number', $address->tax_number) }}"
                           class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
                </div>
            </div>
        </div>

        <div>
            <label class="block text-sm font-600 mb-1.5">Telefon</label>
            <input name="phone" value="{{ old('phone', $address->phone) }}" required
                   class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-600 mb-1.5">İl</label>
                <input name="city" value="{{ old('city', $address->city) }}" required
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
                @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-600 mb-1.5">İlçe</label>
                <input name="district" value="{{ old('district', $address->district) }}" required
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
                @error('district')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-600 mb-1.5">Mahalle</label>
                <input name="neighborhood" value="{{ old('neighborhood', $address->neighborhood) }}"
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-600 mb-1.5">Posta Kodu</label>
                <input name="postal_code" value="{{ old('postal_code', $address->postal_code) }}"
                       class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">
            </div>
        </div>

        <div>
            <label class="block text-sm font-600 mb-1.5">Açık Adres</label>
            <textarea name="address" rows="3" required
                      class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:border-leaf-400 focus:outline-none">{{ old('address', $address->address) }}</textarea>
            @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_default" value="1" {{ old('is_default', $address->is_default) ? 'checked' : '' }} class="rounded border-paper text-leaf-600">
            Varsayılan adres olsun
        </label>

        <button type="submit" class="btn-leaf w-full !rounded-lg">{{ $address->exists ? 'Güncelle' : 'Adresi Kaydet' }}</button>
    </form>
</div>
@endsection
