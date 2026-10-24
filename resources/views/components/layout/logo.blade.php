{{-- Site logo linking to the top of the page; the box is reserved from the intrinsic ratio.
     A parent can resize it responsively through the --logo-h custom property.
     A wide logo (e.g. a text-only wordmark) is capped at $maxWidth and gets shorter. --}}
@props(['logo' => null, 'name', 'height' => 36, 'maxWidth' => 200, 'textOutline' => false, 'outlined' => false])
@php
    $width = null;

    if ($logo && ($logo['height'] ?? 0) > 0) {
        $ratio = $logo['width'] / $logo['height'];
        $width = (int) round(min($ratio * $height, $maxWidth));
        $height = (int) round($width / $ratio);
    }
@endphp
<a href="{{ \App\Support\Links::section('home') }}" {{ $attributes->class('site-logo inline-flex shrink-0 items-center rounded-md') }}>
    @if ($logo)
        <span class="relative inline-flex">
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
                @class(['w-auto', 'opacity-0' => $textOutline])
                decoding="async"
            >
        </picture>
        @if ($textOutline)
            {{-- The logo drawn in three clipped layers over the (invisible) sizing image: the round icon, the
                 EKASA line and the COLD CHAIN line. The last one is nudged down a little so the two lines of
                 lettering do not touch, and the two text layers can take a thin white outline (see
                 .logo-part in app.css). The clip positions belong to the current logo file.
                 The layers reuse the WebP the sizing image already downloaded (the original can be ~110 KB). --}}
            @php($partSrc = $logo['webp'] ?: $logo['url'])
            <img src="{{ $partSrc }}" alt="" aria-hidden="true" class="logo-part logo-part--icon" decoding="async">
            <img src="{{ $partSrc }}" alt="" aria-hidden="true" @class(['logo-part logo-part--line1 logo-part--text', 'logo-outline--always' => $outlined]) decoding="async">
            <img src="{{ $partSrc }}" alt="" aria-hidden="true" @class(['logo-part logo-part--line2 logo-part--text', 'logo-outline--always' => $outlined]) decoding="async">
        @endif
        </span>
    @else
        <span class="text-xl font-extrabold tracking-tight text-white">{{ $name }}</span>
    @endif
</a>
