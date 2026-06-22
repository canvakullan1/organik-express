@extends('layouts.storefront')

@section('title', ($page->meta_title ?: $page->title) . ' — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->excerpt ?? $page->content), 150))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-12">
    <nav class="flex items-center gap-1.5 text-sm text-bark/50 mb-6">
        <a href="{{ route('home') }}" class="hover:text-leaf-700">Anasayfa</a>
        <span>/</span>
        <span class="text-bark font-500">{{ $page->title }}</span>
    </nav>

    <article>
        <h1 class="font-display text-3xl sm:text-4xl font-600 text-bark">{{ $page->title }}</h1>
        @if($page->excerpt)
            <p class="mt-3 text-lg text-bark/60">{{ $page->excerpt }}</p>
        @endif

        <div class="prose prose-leaf max-w-none mt-8 text-bark/80 leading-relaxed
                    prose-headings:font-display prose-headings:text-bark prose-a:text-leaf-700">
            {!! $page->content !!}
        </div>
    </article>
</div>
@endsection
