{{--
    Public layout. Everything crawlers need (meta, JSON-LD, content) is in the HTML;
    JavaScript only adds animation and interaction.
--}}
@props(['seo', 'settings', 'jsonLd' => null])
@php
    use App\Support\MediaPresenter;
    use Illuminate\Support\Facades\Vite;

    $preload = $seo['preload'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="{{ $seo['themeColor'] }}">
        <link rel="manifest" href="/site.webmanifest">

        <title>{{ $seo['title'] }}</title>
        <meta name="description" content="{{ $seo['description'] }}">
        <meta name="robots" content="{{ ($seo['noindex'] ?? false) ? 'noindex, nofollow' : 'index, follow, max-image-preview:large' }}">
        <link rel="canonical" href="{{ $seo['canonical'] }}">
        @foreach ($seo['alternates'] as $hreflang => $url)
            <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $url }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ $seo['alternates'][\App\Support\Locales::default()] }}">

        <meta property="og:type" content="website">
        <meta property="og:locale" content="{{ $seo['ogLocale'] }}">
        @foreach ($seo['ogAlternates'] as $ogLocale)
            <meta property="og:locale:alternate" content="{{ $ogLocale }}">
        @endforeach
        <meta property="og:site_name" content="{{ $seo['siteName'] }}">
        <meta property="og:title" content="{{ $seo['title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta property="og:url" content="{{ $seo['canonical'] }}">
        @if ($seo['image'])
            <meta property="og:image" content="{{ $seo['image']['url'] }}">
            <meta property="og:image:width" content="{{ $seo['image']['width'] }}">
            <meta property="og:image:height" content="{{ $seo['image']['height'] }}">
            <meta property="og:image:alt" content="{{ $seo['imageAlt'] }}">
        @endif
        <meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $seo['title'] }}">
        <meta name="twitter:description" content="{{ $seo['description'] }}">
        @if ($seo['image'])
            <meta name="twitter:image" content="{{ $seo['image']['url'] }}">
        @endif

        @if ($favicon = $settings['favicon'])
            @if ($favicon['sizes'][32] !== $favicon['url'])
                <link rel="icon" href="{{ $favicon['sizes'][32] }}" type="image/png" sizes="32x32">
            @endif
            <link rel="icon" href="{{ $favicon['url'] }}" type="{{ $favicon['mime'] }}">
            <link rel="apple-touch-icon" href="{{ $favicon['sizes'][180] }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
        @endif

        {{-- Marks the page as JS-enabled; animation hiding classes only apply with this.
             Removed again if Alpine has not started after 3s (e.g. the bundle failed). --}}
        <script>
            (function (html) {
                html.classList.add('js');
                if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) html.classList.add('js-anim');
                setTimeout(function () { if (!window.Alpine) html.classList.remove('js', 'js-anim'); }, 3000);
            })(document.documentElement);
        </script>

        @if ($preload)
            {{-- Same srcset/sizes as the <img>, so the browser reuses this request. --}}
            <link rel="preload" as="image" href="{{ $preload['large']['url'] }}" imagesrcset="{{ MediaPresenter::srcset($preload) }}" imagesizes="100vw" fetchpriority="high">
        @endif
        <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/plus-jakarta-sans-latin-400-normal.woff2') }}" crossorigin>
        <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/plus-jakarta-sans-latin-800-normal.woff2') }}" crossorigin>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Brand colors changed in the admin (Pengaturan Situs › Warna); overrides the tokens in app.css. --}}
        @if ($settings['colorOverrides'])
            <style>:root{@foreach ($settings['colorOverrides'] as $name => $hex)--color-{{ $name }}:{{ $hex }};@endforeach}</style>
        @endif

        @if ($jsonLd)
            <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @endif
    </head>
    <body class="bg-white font-sans text-ink antialiased">
        {{ $slot }}
        <x-layout.whatsapp-float :settings="$settings" />
    </body>
</html>
