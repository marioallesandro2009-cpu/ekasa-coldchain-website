{{--
    Section heading: small eyebrow + title + optional description, fades in once visible.
    align: left | center; level: heading level (2 by default); tone: light | dark (dark = on a dark background).
    split: the title comes in word by word (splitText); the eyebrow and description fade in on their own.
--}}
@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'align' => 'left',
    'level' => 2,
    'tone' => 'light',
    'id' => null,
    'split' => false,
])
@php
    $tag = 'h'.max(2, min(6, (int) $level));
    $centered = $align === 'center';
    // Without split the whole block fades in; with split each part animates itself.
    $fade = 'x-data="animatedContent" x-intersect.once="show()" data-animate';
@endphp
<div
    {{ $attributes->class(['max-w-3xl', 'mx-auto text-center' => $centered]) }}
    @unless ($split) {!! $fade !!} @endunless
>
    @if ($eyebrow)
        <p @class([
            'text-sm font-bold tracking-[0.14em] uppercase',
            'text-highlight' => $tone === 'dark',
            'text-brand' => $tone !== 'dark',
        ]) @if ($split) {!! $fade !!} @endif>{{ $eyebrow }}</p>
    @endif
    <{{ $tag }}
        @if ($id) id="{{ $id }}" @endif
        @if ($split) x-data="splitText" data-split-text="{{ $tag }}" @endif
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
        ]) @if ($split) {!! $fade !!} data-delay="0.2" @endif>{{ $description }}</p>
    @endif
</div>
