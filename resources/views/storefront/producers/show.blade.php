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

    <div class="grid md:grid-cols-2 gap-8 items-start mb-12">
        <div class="aspect-square rounded-3xl bg-paper overflow-hidden">
            @if($producer->image)
                <img src="{{ asset('storage/' . $producer->image) }}" alt="{{ $producer->name }}" class="size-full object-cover">
            @endif
        </div>
        <div>
            @if($producer->location)<span class="chip bg-clay-50 text-clay-700">{{ $producer->location }}</span>@endif
            <h1 class="mt-3 font-display text-3xl sm:text-4xl font-700 text-bark">{{ $producer->name }}</h1>
            @if($producer->short_description)<p class="mt-3 text-lg text-bark/70">{{ $producer->short_description }}</p>@endif
            @if($producer->story)
                <div class="prose prose-leaf max-w-none mt-5 text-bark/80 leading-relaxed prose-headings:font-display prose-headings:text-bark prose-a:text-leaf-700">
                    {!! $producer->story !!}
                </div>
            @endif
        </div>
    </div>

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
