{{--
    Button / link button.
    variant: primary (accent, main CTA only) | secondary (outline) | ghost
    tone:    dark (on navy/photos) | light (on white/surface) - affects secondary and ghost
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
        'primary' => 'bg-accent text-navy-dark hover:bg-accent-hover',
        'secondary' => $tone === 'dark'
            ? 'border border-white/70 text-white hover:bg-white/10'
            : 'border border-navy/30 text-navy hover:border-navy hover:bg-navy/5',
        'ghost' => $tone === 'dark'
            ? 'text-white hover:bg-white/10'
            : 'text-navy hover:bg-navy/5',
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
