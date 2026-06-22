@extends('layouts.storefront')

@section('title', ($post->meta_title ?: $post->title) . ' — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@section('meta_description', $post->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?? $post->content), 150))
@if($post->cover_url)@section('og_image', $post->cover_url)@endif

@push('schema')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org', '@type' => 'Article',
    'headline' => $post->title,
    'image' => $post->cover_url,
    'datePublished' => optional($post->published_at)->toAtomString(),
    'dateModified' => $post->updated_at?->toAtomString(),
    'author' => ['@type' => 'Organization', 'name' => app(\App\Settings\GeneralSettings::class)->site_name],
    'publisher' => ['@type' => 'Organization', 'name' => app(\App\Settings\GeneralSettings::class)->site_name],
    'description' => $post->excerpt,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<article class="mx-auto max-w-3xl px-4 py-10">
    <nav class="flex items-center gap-1.5 text-sm text-bark/50 mb-6">
        <a href="{{ route('home') }}" class="hover:text-leaf-700">Anasayfa</a><span>/</span>
        <a href="{{ route('blog.index') }}" class="hover:text-leaf-700">Blog</a><span>/</span>
        <span class="text-bark font-500 line-clamp-1">{{ $post->title }}</span>
    </nav>

    <header>
        @if($post->category)<a href="{{ route('blog.index', ['kategori' => $post->category->slug]) }}" class="chip bg-leaf-50 text-leaf-700">{{ $post->category->name }}</a>@endif
        <h1 class="mt-3 font-display text-3xl sm:text-4xl font-600 text-bark leading-tight">{{ $post->title }}</h1>
        <div class="mt-3 text-sm text-bark/50">{{ optional($post->published_at)->translatedFormat('d F Y') }}@if($post->author) · {{ $post->author->name }}@endif</div>
    </header>

    @if($post->video_embed_url)
        <div class="mt-6 overflow-hidden rounded-2xl bg-bark shadow-sm aspect-video">
            <iframe src="{{ $post->video_embed_url }}" title="{{ $post->title }}"
                    class="size-full" loading="lazy" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
        </div>
    @elseif($post->cover_url)
        <img src="{{ $post->cover_url }}" alt="{{ $post->title }}" class="mt-6 w-full rounded-2xl">
    @endif

    <div class="prose prose-leaf max-w-none mt-8 text-bark/80 leading-relaxed prose-headings:font-display prose-headings:text-bark prose-a:text-leaf-700">
        {!! $post->content !!}
    </div>

    @if($related->isNotEmpty())
        <div class="mt-14 border-t border-paper pt-8">
            <h2 class="font-display text-xl font-600 text-bark mb-5">İlgili Yazılar</h2>
            <div class="grid sm:grid-cols-3 gap-5">
                @foreach($related as $r)
                    <a href="{{ route('blog.show', $r->slug) }}" class="group block">
                        <div class="aspect-[16/10] rounded-xl bg-paper overflow-hidden mb-2">
                            @if($r->cover_url)<img src="{{ $r->cover_url }}" alt="" class="size-full object-cover group-hover:scale-105 transition duration-500">@endif
                        </div>
                        <h3 class="font-600 text-sm text-bark group-hover:text-leaf-700 line-clamp-2">{{ $r->title }}</h3>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</article>
@endsection
