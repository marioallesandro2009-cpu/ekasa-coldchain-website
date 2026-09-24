{{-- Standard width (1280px) and gutter (16px mobile, 24px from md). --}}
@props(['as' => 'div'])
<{{ $as }} {{ $attributes->class('container-site') }}>
    {{ $slot }}
</{{ $as }}>
