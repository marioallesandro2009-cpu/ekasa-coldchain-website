{{--
    Temperature zones: a vertical thermometer (SVG, one colour per zone from the brand
    palette, filling from the bulb once visible) beside the zone cards.
--}}
@props(['block', 'service', 'settings', 'first' => false])
@php
    // Tube inner area: y 30 (top of the scale) to 390 (bottom); the bulb below.
    $y = fn (float $percent): float => round(390 - $percent * 3.6, 1);
    $coldest = collect($block['zones'])->sortBy('min')->first();
@endphp
<x-ui.section :id="$block['id']" :variant="$block['variant']" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <x-ui.section-heading split :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title'] ?? $service['title']" :description="$block['description']" />
    <div class="mt-10 grid items-center gap-10 sm:mt-12 md:grid-cols-[200px_1fr] lg:gap-16" x-data="animatedContent" x-intersect.once="show()" data-draw>
        <svg viewBox="0 0 180 460" class="mx-auto h-[360px] w-auto sm:h-[440px]" role="img" aria-label="{{ collect($block['zones'])->map(fn ($zone) => $zone['label'].' '.$zone['min'].' … '.$zone['max'].' °C')->implode(', ') }}">
            {{-- Scale --}}
            @foreach ($block['ticks'] as $tick)
                <line x1="104" x2="114" y1="{{ $y($tick['position']) }}" y2="{{ $y($tick['position']) }}" class="stroke-ink/30" stroke-width="1.5" />
                <text x="120" y="{{ $y($tick['position']) + 4 }}" class="fill-ink-muted text-[12px] tabular-nums">{{ $tick['value'] }}°</text>
            @endforeach
            {{-- Tube --}}
            <rect x="46" y="18" width="48" height="384" rx="24" class="fill-surface stroke-ink/15" stroke-width="2" />
            <g class="thermo-fill">
                @foreach ($block['zones'] as $zone)
                    <rect
                        x="56" width="28"
                        y="{{ $y($zone['top']) }}"
                        height="{{ max(4, $y($zone['bottom']) - $y($zone['top'])) }}"
                        class="zone-{{ $zone['key'] }}" style="fill: var(--zone)"
                    />
                @endforeach
            </g>
            {{-- Bulb, in the colour of the coldest zone --}}
            <circle cx="70" cy="416" r="32" class="fill-surface stroke-ink/15" stroke-width="2" />
            <circle cx="70" cy="416" r="22" class="zone-{{ $coldest['key'] }}" style="fill: var(--zone)" />
        </svg>
        <ul class="grid gap-4 sm:grid-cols-2 md:grid-cols-1 lg:grid-cols-2" role="list">
            @foreach ($block['zones'] as $i => $zone)
                <li
                    @class(['zone-'.$zone['key'], 'flex gap-4 rounded-card border border-ink/10 p-5', 'bg-white' => $block['variant'] === 'surface', 'bg-surface' => $block['variant'] !== 'surface'])
                    x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="{{ $i * 0.1 }}"
                >
                    <span class="mt-1 h-10 w-2 shrink-0 rounded-full" style="background: var(--zone)" aria-hidden="true"></span>
                    <div>
                        <h3 class="font-bold">{{ $zone['label'] }}</h3>
                        <p class="mt-0.5 text-2xl font-extrabold tracking-tight tabular-nums">{{ $zone['min'] }} … {{ $zone['max'] }} °C</p>
                        @if ($zone['commodities'])
                            <p class="mt-1 text-sm text-ink-muted">{{ $zone['commodities'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</x-ui.section>
