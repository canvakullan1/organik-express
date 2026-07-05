@extends('layouts.storefront')

@section('title', 'Sertifikalarımız — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@section('meta_description', 'Ürünlerimizin taşıdığı organik ve gıda güvenliği standartları ile üretici ve tedarikçilerimizin sertifikaları. Dürüstlük, şeffaflık ve doğaya saygı.')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-12">
    <header class="text-center max-w-2xl mx-auto mb-12">
        <h1 class="font-display text-3xl sm:text-4xl font-700 text-bark">Sertifikalar</h1>
        <p class="mt-3 text-bark/60 leading-relaxed">Sattığımız ürünlerin arkasında belgeli bir üretim ve tedarik zinciri var. Aşağıda ürünlerimizin taşıdığı standartları ve birlikte çalıştığımız üretici ile tedarikçilerin sertifikalarını bulabilirsiniz.</p>
    </header>

    @php
        $standards = $certificates->where('group', 'standart');
        $supplierCerts = $certificates->where('group', 'tedarikci')->groupBy('label');
    @endphp

    @if($certificates->isEmpty())
        <div class="rounded-2xl border border-dashed border-leaf-200 bg-white p-12 text-center">
            <p class="text-bark/60">Sertifika bilgileri yakında eklenecek.</p>
        </div>
    @endif

    {{-- 1) Ürünlerimizin taşıdığı standartlar --}}
    @if($standards->isNotEmpty())
        <section class="mb-16">
            <h2 class="font-display text-2xl font-700 text-bark mb-1">Ürünlerimizin Taşıdığı Standartlar</h2>
            <p class="text-sm text-bark/55 mb-6">Ürünlerimiz bu ulusal ve uluslararası standartlara göre üretilir ve denetlenir.</p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($standards as $c)
                    <div class="flex items-start gap-4 rounded-2xl border border-paper bg-white p-5">
                        <div class="size-16 shrink-0 grid place-items-center">
                            @if($c->image)
                                <img src="{{ asset('storage/' . $c->image) }}" alt="{{ $c->name }}" class="size-full object-contain">
                            @else
                                <svg class="size-10 text-leaf-300" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-700 text-bark leading-snug">{{ $c->name }}</h3>
                            @if($c->description)<p class="mt-1.5 text-sm text-bark/60 leading-relaxed">{{ $c->description }}</p>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- 2) Üretici ve tedarikçilerimizin belgeleri --}}
    @if($supplierCerts->isNotEmpty())
        <section>
            <h2 class="font-display text-2xl font-700 text-bark mb-1">Üretici ve Tedarikçilerimizin Belgeleri</h2>
            <p class="text-sm text-bark/55 mb-8">Ürünlerini sunduğumuz sertifikalı üretici ve tedarikçilerin resmî belgeleri. Belge sahiplerinin bilgisi dahilinde paylaşılmıştır; her belge ilgili firmaya aittir. Büyütmek için üzerine tıklayın.</p>

            @foreach($supplierCerts as $holder => $certs)
                <div class="mb-10">
                    <h3 class="flex items-center gap-2 font-display text-lg font-700 text-bark mb-4">
                        <svg class="size-5 text-leaf-600" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        {{ $holder }}
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($certs as $c)
                            <figure class="group">
                                <a href="{{ asset('storage/' . $c->image) }}" target="_blank" rel="noopener"
                                   class="relative block aspect-[3/4] rounded-xl border border-paper bg-white overflow-hidden hover:border-leaf-300 hover:shadow-md transition">
                                    @if($c->image)
                                        <img src="{{ asset('storage/' . $c->image) }}" alt="{{ $c->name }}" loading="lazy" class="size-full object-contain p-1.5 group-hover:scale-[1.03] transition duration-300">
                                    @endif
                                    <span class="absolute bottom-1.5 right-1.5 grid size-7 place-items-center rounded-lg bg-bark/70 text-white opacity-0 group-hover:opacity-100 transition" aria-hidden="true">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M20.25 3.75v4.5m0-4.5h-4.5m4.5 0L15 9m5.25 11.25v-4.5m0 4.5h-4.5m4.5 0L15 15M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15"/></svg>
                                    </span>
                                </a>
                                <figcaption class="mt-2 text-xs text-bark/60 leading-snug">{{ $c->name }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>
    @endif
</div>
@endsection
