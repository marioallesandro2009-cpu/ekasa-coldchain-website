{{--
    Locations: a hexagon dot map of the area (SVG made in code, land below a simplified
    coastline) with pulsing brand hexagons, and the location cards beside it
    (animatedList). A marker or "Details" opens a side panel with photo, address,
    zones, hours, phone, a dimmed OpenStreetMap map (Leaflet, loaded on first open)
    and a Google Maps button. Without JS: the cards with their Google Maps links.
--}}
@props(['block', 'service', 'settings', 'first' => false])
@php
    $map = $block['map'];
    $pattern = $block['id'].'-dots';
    $panelLocations = collect($block['items'])->map(fn (array $item): array => [
        ...$item,
        'photo' => $item['photo'] ? ['src' => $item['photo']['small']['url'], 'srcset' => \App\Support\MediaPresenter::srcset($item['photo'])] : null,
    ])->all();
@endphp
<x-ui.section :id="$block['id']" :variant="$block['variant']" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <div
        x-data="locationMap({ locations: @js($panelLocations) })"
        @keydown.escape.window="close()"
    >
        <x-ui.section-heading split :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title'] ?? $service['title']" :description="$block['description']" />

        <div class="mt-10 grid gap-8 sm:mt-12 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-start">
            {{-- Map (decorative for screen readers: the list below has the same places). --}}
            <div class="relative overflow-hidden rounded-card bg-dark" style="aspect-ratio: {{ $map['width'] }} / {{ $map['height'] }}">
                <svg viewBox="0 0 {{ $map['width'] }} {{ $map['height'] }}" class="absolute inset-0 size-full" aria-hidden="true" focusable="false">
                    <defs>
                        {{-- Hexagonal dot lattice: rows 14 apart, every other row shifted by half. --}}
                        <pattern id="{{ $pattern }}" width="16" height="28" patternUnits="userSpaceOnUse">
                            <circle cx="4" cy="7" r="2.6" class="fill-highlight/50" />
                            <circle cx="12" cy="21" r="2.6" class="fill-highlight/50" />
                        </pattern>
                    </defs>
                    <path d="{{ $map['land'] }}" fill="url(#{{ $pattern }})" />
                    @foreach ($map['places'] as $place)
                        <text x="{{ $place['x'] }}" y="{{ $place['y'] }}" text-anchor="middle" class="fill-white/45 text-[22px] font-semibold tracking-[0.18em] uppercase">{{ $place['name'] }}</text>
                    @endforeach
                </svg>
                @foreach ($block['items'] as $item)
                    @if ($item['x'] !== null)
                        <a
                            href="#{{ $block['id'] }}-{{ $item['id'] }}"
                            class="group absolute -translate-x-1/2 -translate-y-1/2 rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-highlight"
                            style="left: {{ $item['x'] }}%; top: {{ $item['y'] }}%"
                            @click.prevent="open({{ $item['id'] }}, $event)"
                        >
                            <span class="relative grid size-7 place-items-center">
                                {{-- Hyperlocal motif: a wider "radar reach" ring behind the usual pulse,
                                     only when this block is filtered to hubs specifically (kindFilter),
                                     not on every location marker site-wide. --}}
                                @if (($block['kindFilter'] ?? null) === 'hub')
                                    <span class="hub-radar absolute inset-0 rounded-full bg-highlight/70" aria-hidden="true"></span>
                                @endif
                                <span class="loc-pulse absolute inset-0 bg-highlight [clip-path:polygon(25%_5%,75%_5%,100%_50%,75%_95%,25%_95%,0_50%)]" aria-hidden="true"></span>
                                <span class="relative size-5 bg-brand-soft ring-white transition-transform duration-200 [clip-path:polygon(25%_5%,75%_5%,100%_50%,75%_95%,25%_95%,0_50%)] group-hover:scale-125" aria-hidden="true"></span>
                            </span>
                            <span class="absolute top-1/2 left-full ml-2 -translate-y-1/2 rounded-md bg-dark/80 px-2 py-0.5 text-xs font-semibold whitespace-nowrap text-white">{{ $item['name'] }}</span>
                        </a>
                    @endif
                @endforeach
            </div>

            {{-- Cards --}}
            <ul x-data="animatedList" data-animated-list class="space-y-4" role="list">
                @foreach ($block['items'] as $item)
                    <li id="{{ $block['id'] }}-{{ $item['id'] }}" @class(['scroll-mt-24 rounded-card border border-ink/10 p-5', 'bg-white' => $block['variant'] === 'surface', 'bg-surface' => $block['variant'] !== 'surface'])>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold">{{ $item['name'] }}</h3>
                                <p class="text-sm text-ink-muted">{{ $item['city'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-semibold text-brand">{{ $item['kind'] }}</span>
                        </div>
                        @if ($item['zones'])
                            <ul class="mt-3 flex flex-wrap gap-1.5" role="list" aria-label="{{ __('Temperature zones') }}">
                                @foreach ($item['zones'] as $zone)
                                    <li class="rounded-full border border-ink/15 px-2.5 py-0.5 text-xs font-medium">{{ $zone }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($item['commodities'])
                            <p class="mt-3 text-sm text-ink-muted">{{ $item['commodities'] }}</p>
                        @endif
                        <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-semibold">
                            <button type="button" class="js-only inline-flex items-center gap-1.5 rounded text-brand hover:underline" @click="open({{ $item['id'] }}, $event)">
                                {{ __('View details') }}<span class="sr-only">: {{ $item['name'] }}</span>
                                <x-ui.icon name="arrow-right" class="size-4" />
                            </button>
                            <a href="{{ $item['mapsUrl'] }}" target="_blank" rel="noopener" class="rounded text-ink-muted hover:text-ink hover:underline">
                                {{ __('Open in Google Maps') }}<span class="sr-only">: {{ $item['name'] }}</span>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Side panel (JS only) --}}
        <div x-show="current" x-cloak class="fixed inset-0 z-[60]" role="dialog" aria-modal="true" :aria-label="current?.name">
            <div class="absolute inset-0 bg-overlay/60" @click="close()" x-show="current" x-transition.opacity></div>
            <div
                class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col overflow-y-auto bg-white text-ink shadow-2xl"
                x-show="current"
                x-transition:enter="transition duration-300 ease-out motion-reduce:transition-none"
                x-transition:enter-start="translate-x-full"
                x-transition:leave="transition duration-200 ease-in motion-reduce:transition-none"
                x-transition:leave-end="translate-x-full"
            >
                <template x-if="current">
                    <div>
                        <div class="relative aspect-[4/3] bg-surface">
                            <template x-if="current.photo">
                                <img :src="current.photo.src" :srcset="current.photo.srcset" sizes="448px" width="448" height="336" alt="" class="absolute inset-0 size-full object-cover">
                            </template>
                            <button
                                type="button"
                                x-ref="close"
                                class="absolute top-3 right-3 grid size-10 place-items-center rounded-full bg-white/90 text-ink shadow hover:bg-white focus-visible:outline-2 focus-visible:outline-brand"
                                @click="close()"
                            >
                                <x-ui.icon name="x" class="size-5" />
                                <span class="sr-only">{{ __('Close') }}</span>
                            </button>
                        </div>
                        <div class="p-6">
                            <span class="rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-semibold text-brand" x-text="current.kind"></span>
                            <h3 class="mt-3 text-2xl font-extrabold" x-text="current.name"></h3>
                            <p class="text-ink-muted" x-text="current.city"></p>
                            <dl class="mt-5 space-y-3 text-sm">
                                <template x-if="current.address"><div><dt class="font-semibold">{{ __('Address') }}</dt><dd class="text-ink-muted" x-text="current.address"></dd></div></template>
                                <template x-if="current.zones.length"><div><dt class="font-semibold">{{ __('Temperature zones') }}</dt><dd class="text-ink-muted" x-text="current.zones.join(', ')"></dd></div></template>
                                <template x-if="current.commodities"><div><dt class="font-semibold">{{ __('Commodities') }}</dt><dd class="text-ink-muted" x-text="current.commodities"></dd></div></template>
                                <template x-if="current.capacity"><div><dt class="font-semibold">{{ __('Capacity') }}</dt><dd class="text-ink-muted" x-text="current.capacity"></dd></div></template>
                                <template x-if="current.hours"><div><dt class="font-semibold">{{ __('Opening hours') }}</dt><dd class="text-ink-muted" x-text="current.hours"></dd></div></template>
                                <template x-if="current.phone"><div><dt class="font-semibold">{{ __('Phone') }}</dt><dd><a class="text-brand hover:underline" :href="'tel:' + current.phone.replace(/[^\d+]/g, '')" x-text="current.phone"></a></dd></div></template>
                            </dl>
                        </div>
                    </div>
                </template>
                <div class="px-6" x-show="current && current.lat !== null">
                    <div x-ref="map" class="loc-map aspect-[4/3] overflow-hidden rounded-card bg-surface"></div>
                </div>
                <div class="p-6">
                    <a :href="current?.mapsUrl" target="_blank" rel="noopener" class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-cta-light-bg px-6 text-[15px] font-semibold text-cta-light-text hover:bg-cta-light-bg-hover">
                        {{ __('Open in Google Maps') }}
                        <x-ui.icon name="arrow-right" class="size-4" />
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-ui.section>
