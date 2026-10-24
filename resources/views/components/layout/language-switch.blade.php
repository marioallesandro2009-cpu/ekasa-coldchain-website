{{--
    Language switch: plain links to the same page in the other language. They
    always open the top of the page (no #anchor, so no jump or scroll after
    switching). The active language is not a link.
--}}
@props(['label', 'size' => 'sm'])
@php
    use App\Support\Locales;

    $current = Locales::current();
@endphp
<div
    role="group"
    aria-label="{{ $label }}"
    {{ $attributes->class([
        'flex items-center font-semibold tracking-wide',
        'text-sm' => $size === 'sm',
        'text-base' => $size === 'lg',
    ]) }}
>
    @foreach (Locales::switchOrder() as $i => $locale)
        @if ($i > 0)
            <span class="mx-1.5 text-white/40" aria-hidden="true">|</span>
        @endif
        @if ($locale === $current)
            <span aria-current="true" @class(['text-white', 'inline-grid min-h-11 min-w-11 place-items-center' => $size === 'lg'])>
                <span aria-hidden="true">{{ strtoupper($locale) }}</span>
                <span class="sr-only" lang="{{ $locale }}">{{ Locales::nativeName($locale) }}</span>
            </span>
        @else
            <a
                href="{{ Locales::pathFor(request(), $locale) }}"
                hreflang="{{ $locale }}"
                lang="{{ $locale }}"
                {{-- Starts with the visible code, so voice control ("click ID") still matches. --}}
                aria-label="{{ strtoupper($locale) }}: {{ __('Read in this language', [], $locale) }}"
                @class([
                    'rounded text-white/60 transition-colors duration-200 hover:text-white',
                    'inline-grid min-h-11 min-w-11 place-items-center' => $size === 'lg',
                ])
            >{{ strtoupper($locale) }}</a>
        @endif
    @endforeach
</div>
