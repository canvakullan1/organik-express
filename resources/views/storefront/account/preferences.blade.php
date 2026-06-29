@extends('layouts.storefront')

@section('title', 'İletişim İzinleri — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10">
    <a href="{{ route('account.index') }}" class="text-sm text-bark/50 hover:text-leaf-700">← Hesabım</a>
    <h1 class="font-display text-2xl font-700 text-bark mt-2 mb-1">İletişim İzinleri</h1>
    <p class="text-bark/55 text-sm mb-6">Kampanya ve bildirimler için hangi kanallardan ulaşmamıza izin verdiğini buradan yönet.</p>

    <form method="POST" action="{{ route('account.preferences.update') }}"
          class="rounded-2xl border border-paper bg-white p-2 sm:p-3 divide-y divide-paper">
        @csrf
        @method('PUT')

        @php
            $rows = [
                ['newsletter', 'E-bülten', 'Yeni ürünler, mevsim ürünleri ve haberlerden e-posta ile haberdar ol.', old('newsletter', $newsletter)],
                ['accepts_marketing_email', 'E-posta ile kampanya & indirim', 'Sana özel indirim ve fırsatları e-posta ile gönderelim.', old('accepts_marketing_email', $user->accepts_marketing_email)],
                ['accepts_sms', 'SMS ile bildirim', 'Sipariş ve kampanya bilgilendirmelerini SMS ile al.', old('accepts_sms', $user->accepts_sms)],
            ];
        @endphp

        @foreach($rows as [$name, $title, $desc, $checked])
            <label class="flex items-start gap-4 p-4 cursor-pointer group"
                   x-data="{ on: {{ $checked ? 'true' : 'false' }} }">
                <div class="flex-1">
                    <p class="font-600 text-bark group-hover:text-leaf-700">{{ $title }}</p>
                    <p class="text-sm text-bark/55 mt-0.5">{{ $desc }}</p>
                </div>
                <input type="checkbox" name="{{ $name }}" value="1" x-model="on" class="sr-only">
                <span class="mt-1 relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200"
                      :class="on ? 'bg-leaf-500' : 'bg-paper'">
                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform duration-200"
                          :class="on ? 'translate-x-5' : 'translate-x-0.5'"></span>
                </span>
            </label>
        @endforeach

        <div class="p-4">
            <button type="submit" class="btn-leaf w-full !rounded-full">Tercihleri Kaydet</button>
        </div>
    </form>

    <p class="text-xs text-bark/45 mt-4 leading-relaxed">
        Sipariş onayı, kargo ve ödeme gibi işlemsel bildirimler bu tercihlerden bağımsız olarak her zaman gönderilir.
        İzinlerini istediğin zaman buradan değiştirebilirsin.
    </p>
</div>
@endsection
