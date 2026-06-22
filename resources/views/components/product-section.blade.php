@props([
    'title',
    'subtitle' => null,
    'products' => null,
    'accent' => 'leaf',
    'href' => null,
])

@if($products && $products->isNotEmpty())
    <section {{ $attributes->merge(['class' => 'mx-auto max-w-7xl px-4 py-9']) }}>
        <div class="mb-5 flex items-baseline justify-between gap-4 border-b border-paper pb-3">
            <div class="flex items-baseline gap-3 min-w-0">
                <h2 class="font-display text-xl sm:text-2xl font-700 tracking-tight text-bark truncate">{{ $title }}</h2>
                @if($subtitle)
                    <p class="hidden sm:block text-sm text-bark/45 truncate">{{ $subtitle }}</p>
                @endif
            </div>
            <a href="{{ $href ?? route('search.index') }}" class="shrink-0 text-sm font-600 text-leaf-700 hover:text-leaf-800">
                Tümünü Gör <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
            @foreach($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    </section>
@endif
