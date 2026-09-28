{{--
    One location card (home page #locations section): photo (fallback icon by
    kind when there is none), name, city+area, kind + zone badges, hours, "Get
    Directions" + "Ask via WhatsApp". Empty fields (hours, zones, photo) are
    left out entirely, not rendered blank.

    Highlight state (:class) and the click-to-fly-to-marker handler (@click)
    read/call the parent locationsIndex Alpine component (resources/js/alpine/
    locationsIndex.js) — this component only renders the look and the id/class
    hooks that component targets.
--}}
@props(['location', 'settings'])
@php
    $waMessage = __("Hi, I'd like to ask about :location.", ['location' => $location['name']]);
    $waHref = $settings['whatsappUrl'] ? $settings['whatsappUrl'].'?text='.rawurlencode($waMessage) : null;
@endphp
<li
    id="location-{{ $location['id'] }}"
    x-data="spotlightCard"
    x-show="activeFilter === 'all' || activeFilter === '{{ $location['kind'] }}'"
    :class="{ 'is-highlighted': highlighted === {{ $location['id'] }} }"
    @click="!$event.target.closest('a') && focusMap({{ $location['id'] }})"
    class="location-card spotlight-card scroll-mt-24 cursor-pointer rounded-card border border-ink/10 bg-white p-5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
>
    <div class="flex gap-4">
        <div class="relative size-20 shrink-0 overflow-hidden rounded-lg bg-surface">
            @if ($location['photo'])
                <img
                    src="{{ $location['photo']['small']['url'] }}"
                    srcset="{{ \App\Support\MediaPresenter::srcset($location['photo']) }}"
                    sizes="80px"
                    alt=""
                    loading="lazy"
                    class="absolute inset-0 size-full object-cover"
                >
            @else
                <span class="grid size-full place-items-center text-ink-muted" aria-hidden="true">
                    <x-ui.icon :name="$location['kind'] === 'hub' ? 'map-pin' : 'warehouse'" class="size-8" />
                </span>
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-bold">{{ $location['name'] }}</h2>
                    <p class="truncate text-sm text-ink-muted">{{ $location['city'] }}</p>
                </div>
                <span class="shrink-0 rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-semibold text-brand">{{ $location['kindLabel'] }}</span>
            </div>
            @if ($location['zones'])
                <ul class="mt-2 flex flex-wrap gap-1.5" role="list" aria-label="{{ __('Temperature zones') }}">
                    @foreach ($location['zones'] as $zone)
                        <li class="rounded-full border border-ink/15 px-2.5 py-0.5 text-xs font-medium">{{ $zone }}</li>
                    @endforeach
                </ul>
            @endif
            @if ($location['hours'])
                <p class="mt-2 flex items-center gap-1.5 text-sm text-ink-muted">
                    <x-ui.icon name="clock" class="size-4 shrink-0" />
                    <span class="truncate">{{ $location['hours'] }}</span>
                </p>
            @endif
        </div>
    </div>
    <div class="mt-4 flex flex-wrap gap-3">
        <a
            href="{{ $location['mapsUrl'] }}"
            target="_blank"
            rel="noopener"
            class="inline-flex h-10 items-center gap-1.5 rounded-full border border-ink/15 px-4 text-sm font-semibold transition-colors hover:border-brand/40 hover:text-brand"
        >
            <x-ui.icon name="map-pin" class="size-4" />
            {{ __('Get Directions') }}
        </a>
        @if ($waHref)
            <a
                href="{{ $waHref }}"
                target="_blank"
                rel="noopener"
                class="inline-flex h-10 items-center gap-1.5 rounded-full bg-brand/10 px-4 text-sm font-semibold text-brand transition-colors hover:bg-brand/15"
            >
                <x-ui.icon name="message-circle" class="size-4" />
                {{ __('Ask via WhatsApp') }}
            </a>
        @endif
    </div>
</li>
