{{--
    Button / link button.
    variant: primary (main CTA) | secondary (outline) | ghost
    tone:    dark (on dark/photos) | light (on white/surface)
    size:    md | lg
    Renders <a> when href is given, otherwise <button>.
--}}
@props([
    'variant' => 'primary',
    'tone' => 'dark',
    'size' => 'md',
    'href' => null,
    'icon' => null,
])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-full font-semibold whitespace-nowrap transition-colors duration-200';

    $sizes = [
        'md' => 'h-11 px-6 text-[15px]',
        'lg' => 'h-12 px-5 text-[15px] sm:h-13 sm:px-7 sm:text-base',
    ];

    $variants = [
        'primary' => $tone === 'dark'
            ? 'bg-cta-dark-bg text-cta-dark-text hover:bg-cta-dark-bg-hover'
            : 'bg-cta-light-bg text-cta-light-text hover:bg-cta-light-bg-hover',
        'secondary' => $tone === 'dark'
            ? 'border border-white/70 text-white hover:bg-white/10'
            : 'border border-brand/30 text-brand hover:border-brand hover:bg-brand/5',
        'ghost' => $tone === 'dark'
            ? 'text-white hover:bg-white/10'
            : 'text-brand hover:bg-brand/5',
    ];

    $classes = [$base, $sizes[$size] ?? $sizes['md'], $variants[$variant] ?? $variants['primary']];
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
        @if ($icon)
            <x-ui.icon :name="$icon" class="size-4.5 transition-transform duration-200 group-hover:translate-x-0.5" />
        @endif
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>
        {{ $slot }}
        @if ($icon)
            <x-ui.icon :name="$icon" class="size-4.5" />
        @endif
    </button>
@endif
