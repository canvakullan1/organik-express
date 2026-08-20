{{--
    Türkçe sayfalama (Laravel default tailwind.blade.php yerine geçer).
    Paginator::useTailwind() bu görünümü kullanır.

    Mobil : Önceki / Sonraki + ortada "Sayfa 1 / 3"
    Masaüstü: "30 üründen 1-12 arası gösteriliyor" + sayfa numaraları

    Öğe adı ($itemLabel) çağıran taraftan verilebilir:
      $products->links(data: ['itemLabel' => 'ürün'])
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Sayfalama">

        {{-- ---------- MOBİL ---------- --}}
        <div class="flex items-center justify-between gap-3 sm:hidden">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="inline-flex items-center gap-1 rounded-full border border-leaf-100 bg-white px-4 py-2 text-sm font-medium text-bark/35 cursor-not-allowed select-none">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Önceki
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-1 rounded-full border border-leaf-200 bg-white px-4 py-2 text-sm font-medium text-bark transition hover:bg-leaf-50 hover:border-leaf-300 active:scale-[0.98]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Önceki
                </a>
            @endif

            <span class="text-sm text-bark/60 tabular-nums">
                Sayfa <span class="font-semibold text-bark">{{ $paginator->currentPage() }}</span> / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-1 rounded-full border border-leaf-200 bg-white px-4 py-2 text-sm font-medium text-bark transition hover:bg-leaf-50 hover:border-leaf-300 active:scale-[0.98]">
                    Sonraki
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span aria-disabled="true" class="inline-flex items-center gap-1 rounded-full border border-leaf-100 bg-white px-4 py-2 text-sm font-medium text-bark/35 cursor-not-allowed select-none">
                    Sonraki
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </div>

        {{-- ---------- MASAÜSTÜ ---------- --}}
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between sm:gap-4">
            <p class="text-sm text-bark/60">
                Toplam <span class="font-semibold text-bark tabular-nums">{{ $paginator->total() }}</span> {{ $itemLabel ?? 'sonuç' }} içinden
                <span class="font-semibold text-bark tabular-nums">{{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }}</span>
                arası gösteriliyor
                {{-- $itemLabel çağıran view'dan gelir: links(data: ['itemLabel' => 'ürün']) --}}
            </p>

            <div class="flex items-center gap-1">
                {{-- Önceki --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Önceki sayfa" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-leaf-100 bg-white text-bark/30 cursor-not-allowed">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Önceki sayfa" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-leaf-200 bg-white text-bark transition hover:bg-leaf-50 hover:border-leaf-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                @endif

                {{-- Sayfa numaraları --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true" class="inline-flex h-9 w-9 items-center justify-center text-sm text-bark/40 select-none">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex h-9 min-w-9 items-center justify-center rounded-full bg-leaf-600 px-3 text-sm font-semibold text-white tabular-nums">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Sayfa {{ $page }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-full border border-leaf-200 bg-white px-3 text-sm font-medium text-bark tabular-nums transition hover:bg-leaf-50 hover:border-leaf-300">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Sonraki --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Sonraki sayfa" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-leaf-200 bg-white text-bark transition hover:bg-leaf-50 hover:border-leaf-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="Sonraki sayfa" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-leaf-100 bg-white text-bark/30 cursor-not-allowed">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
