{{--
    Section heading: small eyebrow + title + optional description, fades in once visible.
    align: left | center; level: heading level (2 by default); tone: light | dark (dark = on navy).
--}}
@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'align' => 'left',
    'level' => 2,
    'tone' => 'light',
    'id' => null,
])
@php
    $tag = 'h'.max(2, min(6, (int) $level));
    $centered = $align === 'center';
@endphp
<div
    {{ $attributes->class(['max-w-3xl', 'mx-auto text-center' => $centered]) }}
    x-data="animatedContent" x-intersect.once="show()"
    data-animate
>
    @if ($eyebrow)
        <p @class([
            'text-sm font-bold tracking-[0.14em] uppercase',
            'text-ice' => $tone === 'dark',
            'text-navy' => $tone !== 'dark',
        ])>{{ $eyebrow }}</p>
    @endif
    <{{ $tag }}
        @if ($id) id="{{ $id }}" @endif
        @class([
            'text-3xl leading-tight font-extrabold tracking-[-0.02em] text-balance sm:text-4xl',
            'mt-3' => $eyebrow,
            'text-white' => $tone === 'dark',
            'text-ink' => $tone !== 'dark',
        ])
    >{{ $title }}</{{ $tag }}>
    @if ($description)
        <p @class([
            'mt-4 text-base leading-relaxed sm:text-lg',
            'text-white/80' => $tone === 'dark',
            'text-ink-muted' => $tone !== 'dark',
        ])>{{ $description }}</p>
    @endif
</div>
