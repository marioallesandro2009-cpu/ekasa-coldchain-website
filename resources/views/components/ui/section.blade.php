{{--
    Page section with consistent vertical spacing (64px mobile, 96px desktop).
    variant: white | surface | dark
--}}
@props([
    'id' => null,
    'variant' => 'white',
    'contained' => true,
])
@php
    $variants = [
        'white' => 'bg-white text-ink',
        'surface' => 'bg-surface text-ink',
        'dark' => 'on-dark bg-dark text-white',
    ];
@endphp
<section
    @if ($id) id="{{ $id }}" @endif
    {{ $attributes->class(['py-16 lg:py-24', $variants[$variant] ?? $variants['white']]) }}
>
    @if ($contained)
        <x-ui.container>{{ $slot }}</x-ui.container>
    @else
        {{ $slot }}
    @endif
</section>
