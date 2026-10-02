{{--
    Home page locations (#locations): a Leaflet map auto-fit to every active
    location, synced with a filterable card list. One-page/parallax site (like
    #services above it) — this is the actual "Locations" section content, not
    a separate route; the navbar's "Locations" link is a plain anchor scroll
    (Links::section), same mechanism as every other non-mega nav item.
--}}
@props(['section', 'locations', 'settings'])
@php
    $dark = $section['background'] === 'dark';
    $cities = collect($locations)->pluck('city')->filter()->unique();
    $zonesOffered = collect($locations)->flatMap(fn (array $l) => $l['zones'])->unique();
    $withCoords = collect($locations)->filter(fn (array $l) => $l['lat'] !== null && $l['lng'] !== null);
    $mapCenter = $withCoords->isNotEmpty()
        ? [$withCoords->avg('lat'), $withCoords->avg('lng')]
        : null;
@endphp
<x-ui.section
    :id="$section['id']"
    :variant="$section['background']"
    class="hex-dot-field relative isolate flex min-h-screen flex-col justify-center"
    aria-labelledby="{{ $section['id'] }}-title"
    x-ignore
    data-alpine-defer
>
    <x-ui.section-heading
        split
        :id="$section['id'].'-title'"
        :eyebrow="$section['eyebrow']"
        :title="$section['title']"
        :description="$section['description']"
        :tone="$dark ? 'dark' : 'light'"
    />

    {{-- Stats: computed from the active locations, not fixed numbers — capacity
         is skipped (free text per location, e.g. "3,000 pallets", not reliably
         summable across locations). --}}
    @if (count($locations))
        <dl class="mt-8 grid grid-cols-3 gap-x-6 gap-y-4 border-t border-ink/10 pt-6 sm:max-w-md">
            @foreach ([
                ['value' => count($locations), 'label' => __('Active locations')],
                ['value' => $cities->count(), 'label' => __('Cities served')],
                ['value' => $zonesOffered->count(), 'label' => __('Temperature zones')],
            ] as $stat)
                <div>
                    <dd class="text-2xl font-extrabold tabular-nums sm:text-3xl">
                        <span class="sr-only">{{ $stat['value'] }}</span>
                        <span aria-hidden="true" x-data="countUp({ to: {{ $stat['value'] }} })" x-intersect.once="start()">{{ $stat['value'] }}</span>
                    </dd>
                    <dt class="mt-1 text-sm text-ink-muted">{{ $stat['label'] }}</dt>
                </div>
            @endforeach
        </dl>
    @endif

    <div
        class="mt-10 sm:mt-12"
        x-data="locationsIndex({
            locations: @js($locations),
            labels: { viewDetails: @js(__('View details')) },
        })"
    >
        {{-- Filter: always all three tabs, even if one kind currently has zero
             matches — an empty tab shows the empty-state message below, it is
             never hidden (kind counts can change any time admin edits data). --}}
        <div class="flex flex-wrap gap-2" role="tablist" aria-label="{{ __('Filter') }}">
            @foreach ([['all', __('All')], ['office', __('Office')], ['pool', __('Pool')]] as [$kind, $label])
                <button
                    type="button"
                    role="tab"
                    @click="setFilter('{{ $kind }}')"
                    :aria-selected="(activeFilter === '{{ $kind }}').toString()"
                    :class="activeFilter === '{{ $kind }}' ? 'bg-brand text-white' : 'bg-surface text-ink-muted hover:text-ink'"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition-colors"
                >{{ $label }}</button>
            @endforeach
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:items-start">
            {{-- Cards: scrollable side. --}}
            <div class="order-2 lg:order-1">
                @if (count($locations) === 0)
                    <p class="rounded-card border border-ink/10 bg-white p-6 text-ink-muted">{{ __('No locations yet.') }}</p>
                @else
                    <p
                        x-show="visibleLocations().length === 0"
                        x-cloak
                        class="rounded-card border border-ink/10 bg-white p-6 text-ink-muted"
                    >{{ __('No locations match this filter yet.') }}</p>

                    <ul x-data="animatedList" data-animated-list class="space-y-4" role="list">
                        @foreach ($locations as $location)
                            <x-sections.location-card :location="$location" :settings="$settings" />
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Map: sticky side (desktop only — fixed height on mobile, normal flow). --}}
            <div class="order-1 lg:order-2 lg:sticky lg:top-24 lg:self-start">
                <div x-ref="map" class="loc-map aspect-[4/3] overflow-hidden rounded-card bg-surface lg:aspect-auto lg:h-[560px]"></div>
                <noscript>
                    <div class="loc-map grid aspect-[4/3] place-items-center rounded-card bg-surface p-6 text-center lg:aspect-auto lg:h-[560px]">
                        <a
                            href="{{ $mapCenter ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($mapCenter[0].','.$mapCenter[1]) : 'https://www.google.com/maps' }}"
                            target="_blank"
                            rel="noopener"
                            class="font-semibold text-brand hover:underline"
                        >{{ __('View all locations on Google Maps') }}</a>
                    </div>
                </noscript>
            </div>
        </div>
    </div>
</x-ui.section>
