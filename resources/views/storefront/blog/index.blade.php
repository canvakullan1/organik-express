@extends('layouts.storefront')

@section('title', 'Blog — ' . app(\App\Settings\GeneralSettings::class)->site_name)
@section('meta_description', 'Organik yaşam, sağlıklı beslenme ve üreticilerimizden haberler.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10">
    <header class="mb-8 text-center max-w-2xl mx-auto">
        
        <h1 class="font-display text-3xl sm:text-4xl font-600 text-bark">Blog</h1>
        <p class="mt-2 text-bark/60">Organik yaşam, sağlıklı beslenme ve üreticilerimizden hikâyeler.</p>
    </header>

    {{-- Kategori filtreleri --}}
    @if($categories->isNotEmpty())
        <div class="flex flex-wrap justify-center gap-2 mb-8">
            <a href="{{ route('blog.index') }}" @class(['chip px-4 py-2', 'bg-leaf-600 text-white' => ! $activeCategory, 'bg-white border border-paper text-bark hover:border-leaf-300' => $activeCategory])>Tümü</a>
            @foreach($categories as $cat)
                <a href="{{ route('blog.index', ['kategori' => $cat->slug]) }}"
                   @class(['chip px-4 py-2', 'bg-leaf-600 text-white' => $activeCategory === $cat->slug, 'bg-white border border-paper text-bark hover:border-leaf-300' => $activeCategory !== $cat->slug])>{{ $cat->name }}</a>
            @endforeach
        </div>
    @endif

    @if($posts->isEmpty())
        <div class="rounded-2xl border border-dashed border-leaf-200 bg-white p-12 text-center">
            <p class="font-display text-xl text-bark">Henüz yazı yok</p>
            <p class="mt-2 text-sm text-bark/60">Yakında içerikler burada olacak.</p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($posts as $post)
                <article class="group flex flex-col rounded-2xl border border-paper bg-white overflow-hidden card-lift">
                    <a href="{{ route('blog.show', $post->slug) }}" class="relative block aspect-[16/10] bg-paper overflow-hidden">
                        @if($post->cover_url)
                            <img src="{{ $post->cover_url }}" alt="{{ $post->title }}" loading="lazy" class="size-full object-cover group-hover:scale-105 transition duration-500">
                        @else
                            <div class="size-full grid place-items-center text-leaf-200">
                                <svg class="size-12" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"/></svg>
                            </div>
                        @endif
                        @if($post->has_video)
                            <span class="absolute inset-0 grid place-items-center bg-black/15 transition group-hover:bg-black/25">
                                <span class="grid size-14 place-items-center rounded-full bg-white/95 text-leaf-800 shadow-lg transition group-hover:scale-110">
                                    <svg class="size-6 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347a1.125 1.125 0 0 1-1.667-.985V5.653Z"/></svg>
                                </span>
                            </span>
                        @endif
                    </a>
                    <div class="flex flex-1 flex-col p-5">
                        @if($post->category)<span class="chip bg-leaf-50 text-leaf-700 w-fit mb-2">{{ $post->category->name }}</span>@endif
                        <h2 class="font-display text-lg font-600 text-bark leading-snug line-clamp-2">
                            <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-leaf-700">{{ $post->title }}</a>
                        </h2>
                        @if($post->excerpt)<p class="mt-2 text-sm text-bark/60 line-clamp-3">{{ $post->excerpt }}</p>@endif
                        <div class="mt-auto pt-4 text-xs text-bark/40">{{ optional($post->published_at)->translatedFormat('d F Y') }}</div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-10">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
