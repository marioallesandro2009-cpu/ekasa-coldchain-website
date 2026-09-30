@props(['item'])
@php
    $logo = $item['logo'];
    $width = $logo['height'] > 0 ? (int) round($logo['width'] / $logo['height'] * 40) : null;
@endphp
<picture>
    @if ($logo['webp'])
        <source srcset="{{ $logo['webp'] }}" type="image/webp">
    @endif
    <img
        src="{{ $logo['url'] }}"
        alt="{{ $item['name'] }}"
        @if ($width) width="{{ $width }}" @endif
        height="40"
        loading="lazy"
        decoding="async"
        class="h-10 w-auto max-w-[160px] object-contain"
    >
</picture>
