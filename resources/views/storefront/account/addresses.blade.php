@extends('layouts.storefront')

@section('title', 'Adreslerim — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('account.index') }}" class="text-sm text-bark/50 hover:text-leaf-700">← Hesabım</a>
            <h1 class="font-display text-2xl font-700 text-bark mt-1">Adreslerim</h1>
        </div>
        <a href="{{ route('account.address.create') }}" class="btn-leaf !rounded-lg">+ Yeni Adres</a>
    </div>

    @if($addresses->isEmpty())
        <div class="rounded-2xl border border-dashed border-leaf-200 bg-white p-10 text-center">
            <p class="text-bark/60">Henüz kayıtlı adresin yok.</p>
            <a href="{{ route('account.address.create') }}" class="btn-leaf !rounded-lg mt-4">İlk adresini ekle</a>
        </div>
    @else
        <div class="grid sm:grid-cols-2 gap-4">
            @foreach($addresses as $a)
                <div class="rounded-2xl border border-paper bg-white p-5">
                    <div class="flex items-start justify-between">
                        <span class="chip bg-leaf-50 text-leaf-700">{{ $a->title }}</span>
                        @if($a->is_default)<span class="chip bg-clay-50 text-clay-700">Varsayılan</span>@endif
                    </div>
                    <p class="mt-3 font-600 text-bark">{{ $a->full_name }}</p>
                    <p class="text-sm text-bark/60">{{ $a->phone }}</p>
                    <p class="mt-2 text-sm text-bark/70 leading-relaxed">{{ $a->address }}, {{ $a->district }}/{{ $a->city }}</p>
                    @if($a->is_corporate)<p class="mt-2 text-xs text-bark/50">{{ $a->company_name }} · VD: {{ $a->tax_office }} · {{ $a->tax_number }}</p>@endif
                    <div class="mt-4 flex gap-3 text-sm">
                        <a href="{{ route('account.address.edit', $a) }}" class="font-600 text-leaf-700 hover:underline">Düzenle</a>
                        <form method="POST" action="{{ route('account.address.destroy', $a) }}" onsubmit="return confirm('Adres silinsin mi?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline">Sil</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
