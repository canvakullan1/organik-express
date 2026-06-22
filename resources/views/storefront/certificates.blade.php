@extends('layouts.storefront')

@section('title', 'Sertifikalarımız — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@section('meta_description', 'Uluslararası standartlara uygunluğumuzu belgeleyen sertifikalarımız. Dürüstlük, şeffaflık ve doğaya saygı.')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-12">
    <header class="text-center max-w-2xl mx-auto mb-10">
        
        <h1 class="font-display text-3xl sm:text-4xl font-700 text-bark">Sertifikalarımız</h1>
        <p class="mt-3 text-bark/60">Dürüstlük, şeffaflık ve doğaya saygı ilkeleriyle; uluslararası standartlara uygunluğumuzu belgeleyen sertifikalarımız.</p>
    </header>

    @if($certificates->isEmpty())
        <div class="rounded-2xl border border-dashed border-leaf-200 bg-white p-12 text-center">
            <p class="text-bark/60">Sertifika bilgileri yakında eklenecek.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($certificates as $cert)
                <div class="flex flex-col sm:flex-row items-start gap-5 rounded-2xl border border-paper bg-white p-5">
                    <div class="size-24 shrink-0 rounded-xl bg-paper overflow-hidden grid place-items-center">
                        @if($cert->image)
                            <img src="{{ asset('storage/' . $cert->image) }}" alt="{{ $cert->name }}" class="size-full object-contain p-2">
                        @else
                            <svg class="size-10 text-leaf-300" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            @if($cert->label)<span class="chip bg-leaf-50 text-leaf-700">{{ $cert->label }}</span>@endif
                            @if($cert->valid_until)<span class="text-xs text-bark/40">{{ $cert->valid_until->format('Y') }} tarihine kadar geçerli</span>@endif
                        </div>
                        <h2 class="mt-1.5 font-700 text-bark">{{ $cert->name }}</h2>
                        @if($cert->description)<p class="mt-1 text-sm text-bark/60 leading-relaxed">{{ $cert->description }}</p>@endif
                        @if($cert->file)
                            <a href="{{ asset('storage/' . $cert->file) }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1.5 text-sm font-600 text-leaf-700 hover:underline">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                Belgeyi görüntüle (PDF)
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
