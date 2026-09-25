@props(['name'])
{{-- Ekasa content icon or Lucide interface icon, inline; decorative by default. --}}
<svg {{ $attributes->merge([
    'xmlns' => 'http://www.w3.org/2000/svg',
    'viewBox' => '0 0 24 24',
    'fill' => 'none',
    'stroke' => 'currentColor',
    'stroke-width' => \App\Support\Icon::strokeWidth($name),
    'stroke-linecap' => 'round',
    'stroke-linejoin' => 'round',
    'aria-hidden' => 'true',
    'focusable' => 'false',
]) }}>{!! \App\Support\Icon::inner($name) !!}</svg>
