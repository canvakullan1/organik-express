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

    @if($page->slug === 'iletisim')
        <div id="iletisim-form" class="mt-10">
            <div class="rounded-3xl border border-bark/10 bg-white p-6 sm:p-8">
                <h2 class="font-display text-2xl font-600 text-bark">Bize Yazın</h2>
                <p class="mt-1 text-bark/60 text-sm">Sorularınız için formu doldurun, en kısa sürede dönüş yapalım.</p>

                @if(session('contact_ok'))
                    <div class="mt-5 rounded-xl bg-leaf-50 border border-leaf-200 text-leaf-800 px-4 py-3 text-sm font-500">
                        ✓ Mesajınız iletildi. Teşekkürler, en kısa sürede size dönüş yapacağız.
                    </div>
                @endif
                @if($errors->any())
                    <div class="mt-5 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                        <ul class="list-disc pl-5 space-y-0.5">
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.send') }}#iletisim-form" class="mt-6 grid gap-4 sm:grid-cols-2">
                    @csrf
                    {{-- honeypot (gizli) --}}
                    <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                    <div>
                        <label class="block text-sm font-500 text-bark/70 mb-1">Ad Soyad *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-xl border border-bark/15 px-4 py-2.5 focus:border-leaf-500 focus:ring-2 focus:ring-leaf-200 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-500 text-bark/70 mb-1">E-posta *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full rounded-xl border border-bark/15 px-4 py-2.5 focus:border-leaf-500 focus:ring-2 focus:ring-leaf-200 outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-500 text-bark/70 mb-1">Telefon</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}"
                               class="w-full rounded-xl border border-bark/15 px-4 py-2.5 focus:border-leaf-500 focus:ring-2 focus:ring-leaf-200 outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-500 text-bark/70 mb-1">Mesajınız *</label>
                        <textarea name="message" rows="5" required
                                  class="w-full rounded-xl border border-bark/15 px-4 py-2.5 focus:border-leaf-500 focus:ring-2 focus:ring-leaf-200 outline-none">{{ old('message') }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-leaf-700 px-6 py-3 text-white font-600 hover:bg-leaf-800 transition">
                            Gönder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
