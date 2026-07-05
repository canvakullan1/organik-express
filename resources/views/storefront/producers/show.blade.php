@extends('layouts.storefront')

@section('title', ($producer->meta_title ?: $producer->name) . ' — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@section('meta_description', $producer->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($producer->short_description ?? $producer->story), 150))

@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Anasayfa', 'item' => route('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Üreticiler', 'item' => route('producers.index')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $producer->name, 'item' => route('producer.show', $producer->slug)],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10">
    <nav class="flex items-center gap-1.5 text-sm text-bark/50 mb-6">
        <a href="{{ route('home') }}" class="hover:text-leaf-700">Anasayfa</a><span>/</span>
        <a href="{{ route('producers.index') }}" class="hover:text-leaf-700">Üreticiler</a><span>/</span>
        <span class="text-bark font-500">{{ $producer->name }}</span>
    </nav>

    <div class="grid md:grid-cols-2 gap-8 items-center mb-12">
        <div class="aspect-[4/3] rounded-3xl bg-paper overflow-hidden ring-1 ring-black/5">
            @if($producer->image)
                <img src="{{ asset('storage/' . $producer->image) }}" alt="{{ $producer->name }}" class="size-full object-cover">
            @else
                <div class="size-full grid place-items-center text-leaf-200">
                    <svg class="size-16" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                </div>
            @endif
        </div>
        <div>
            @if($producer->location)<span class="chip bg-clay-50 text-clay-700">{{ $producer->location }}</span>@endif
            <h1 class="mt-3 font-display text-3xl sm:text-4xl font-700 text-bark">{{ $producer->name }}</h1>
            @if($producer->short_description)<p class="mt-4 text-lg text-bark/70 leading-relaxed">{{ $producer->short_description }}</p>@endif
        </div>
    </div>

    {{-- Hikâye --}}
    @if($producer->story)
        <div class="prose prose-leaf max-w-3xl mb-14 text-bark/80 leading-relaxed prose-headings:font-display prose-headings:text-bark prose-headings:font-700 prose-a:text-leaf-700">
            {!! $producer->story !!}
        </div>
    @endif

    {{-- Tanıtım Videoları --}}
    @if(!empty($producer->videos))
        @php $vc = count($producer->videos); @endphp
        <div class="border-t border-paper pt-10 mb-14">
            <h2 class="font-display text-2xl font-700 text-bark mb-6">Tanıtım Videoları</h2>
            <div class="grid gap-6 {{ $vc >= 3 ? 'sm:grid-cols-2 lg:grid-cols-3' : ($vc === 2 ? 'sm:grid-cols-2' : 'max-w-3xl') }}">
                @foreach($producer->videos as $v)
                    <figure>
                        <div class="aspect-video rounded-2xl overflow-hidden bg-bark/5 ring-1 ring-black/5 shadow-sm">
                            <iframe class="size-full" src="https://www.youtube-nocookie.com/embed/{{ $v['id'] }}"
                                    title="{{ $v['title'] ?? $producer->name }}" loading="lazy"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                        </div>
                        @if(!empty($v['title']))<figcaption class="mt-2.5 text-sm text-bark/55">{{ $v['title'] }}</figcaption>@endif
                    </figure>
                @endforeach
            </div>
        </div>
    @endif

    @if($products->isNotEmpty())
        <div class="border-t border-paper pt-10">
            <h2 class="font-display text-2xl font-700 text-bark mb-6">{{ $producer->name }} Ürünleri</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
                @foreach($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        </div>
    @endif
</div>
@endsection
