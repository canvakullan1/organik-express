@props([
    'price' => null,
    'compare' => null,
    'size' => 'md',
])

@php
    $fmt = fn ($v) => '₺' . number_format((float) $v, 2, ',', '.');
    $hasDiscount = $compare && $compare > $price;
    $sizes = [
        'sm' => 'text-base',
        'md' => 'text-lg',
        'lg' => 'text-2xl',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-baseline gap-2 flex-wrap']) }}>
    @if(! is_null($price))
        <span class="font-display font-600 text-leaf-800 tnum {{ $sizes[$size] ?? $sizes['md'] }}">{{ $fmt($price) }}</span>
        @if($hasDiscount)
            <span class="text-sm text-bark/40 line-through tnum">{{ $fmt($compare) }}</span>
        @endif
    @else
        <span class="text-sm text-bark/50">Fiyat için tıklayın</span>
    @endif
</div>
