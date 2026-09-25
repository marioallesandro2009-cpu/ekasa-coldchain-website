{{-- Site logo linking to the top of the page; the box is reserved from the intrinsic ratio.
     A parent can resize it responsively through the --logo-h custom property.
     A wide logo (e.g. a text-only wordmark) is capped at $maxWidth and gets shorter. --}}
@props(['logo' => null, 'name', 'height' => 36, 'maxWidth' => 200])
@php
    $width = null;

    if ($logo && ($logo['height'] ?? 0) > 0) {
        $ratio = $logo['width'] / $logo['height'];
        $width = (int) round(min($ratio * $height, $maxWidth));
        $height = (int) round($width / $ratio);
    }
@endphp
<a href="#home" {{ $attributes->class('inline-flex shrink-0 items-center rounded-md') }}>
    @if ($logo)
        <picture>
            @if ($logo['webp'])
                <source srcset="{{ $logo['webp'] }}" type="image/webp">
            @endif
            <img
                src="{{ $logo['url'] }}"
                alt="{{ $name }}"
                @if ($width) width="{{ $width }}" @endif
                height="{{ $height }}"
                style="height: var(--logo-h, {{ $height }}px)"
                class="w-auto"
                decoding="async"
            >
        </picture>
    @else
        <span class="text-xl font-extrabold tracking-tight text-white">{{ $name }}</span>
    @endif
</a>
