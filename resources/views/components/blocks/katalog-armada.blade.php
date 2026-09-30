{{--
    Fleet catalogue. Showcase (default, "vehicle configurator" style): the truck photo
    is the dominant element (box 75:35 / 15:7 on desktop, shorter 16:10 on phones,
    object-cover — the crop only trims the empty studio margin above/below the truck,
    never the vehicle itself, see docs/screenshots/foto-pengganti/armada-75-35-simulasi.png),
    with the name + type badge overlaid top-left (scrim behind for contrast) and a
    compact floating spec card top-right on desktop (below the photo, in normal flow,
    on phones so it never covers the truck). Large prev/next arrows sit on the panel's
    left/right edges; a thumbnail strip (name below each) sits under the panel.
    Grid: one card per truck, with a Semua/Reefer/Dry filter when both types exist.
    Without JS: every truck listed one after another, no navigation.

    Special block "bigger stage" (REDESIGN MATANG, Bagian D): forced dark
    background and bigger padding, no named per-service motif here (only
    zona_suhu, diagram_rute and alur_end_to_end have one). The Showcase photo
    already carries its own dark scrim regardless of section background, so
    only the Grid layout's card background (bg-white/bg-surface, which assumed
    a light section) needed a dark-aware class.
--}}
@props(['block', 'service', 'settings', 'first' => false])
@php
    $items = $block['items'];
    $count = count($items);
    $announce = collect($items)->values()->map(fn (array $item, int $i): string => __('Truck :current of :total: :name', ['current' => $i + 1, 'total' => $count, 'name' => $item['name']]))->all();
@endphp
<x-ui.section :id="$block['id']" variant="dark" class="py-20! lg:py-28!" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <x-ui.section-heading split align="center" tone="dark" :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title'] ?? $service['title']" :description="$block['description']" />

    @if ($block['layout'] === 'showcase')
        <div
            class="mt-8 sm:mt-10"
            x-data="fleetShowcase({ count: {{ $count }}, announce: @js($announce) })"
        >
            <p class="sr-only" aria-live="polite" x-text="message"></p>

            {{-- overflow-hidden with NO rounded-* here (each slide's own
                 .fleet-hero-panel already rounds the photo itself, so this
                 outer clip's own corners are never actually seen): pairing
                 overflow-hidden with a border-radius on this specific element
                 reproduced a real Chromium bug — the mobile eyebrow's first
                 glyph (a capital R or D, both letters with a near-zero left
                 side-bearing straight stem) rendered with that stem shaved
                 off, only at small sizes, only inside a rounded clip mask.
                 Confirmed by elimination (tracking, uppercase, transform,
                 opacity, font-weight, padding all ruled out first): a plain
                 rectangular overflow-hidden clip never triggers it, a rounded
                 one (via border-radius OR an equivalent clip-path) always
                 does, regardless of the text's own styling or position. --}}
            <div class="relative overflow-hidden" @pointerdown="pointerDown($event)" @pointerup="pointerUp($event)">
                <div class="showcase-track flex flex-col gap-10 lg:gap-14" :style="trackStyle()">
                    @foreach ($items as $i => $item)
                        <div
                            id="{{ $block['id'] }}-truck-{{ $i }}"
                            class="showcase-slide"
                            role="tabpanel"
                            aria-labelledby="{{ $block['id'] }}-tab-{{ $i }}"
                            x-bind:aria-hidden="(index !== {{ $i }}).toString()"
                            x-bind:inert="index !== {{ $i }}"
                            x-bind:class="index === {{ $i }} ? 'is-active' : ''"
                            x-data="animatedContent" x-intersect.once="show()" data-draw
                        >
                            {{-- Mobile content order: eyebrow → title → description → image → CTA →
                                 specs → fleet selector. Below lg this info sits in normal flow on the
                                 section's own dark background (no photo behind it yet, so no scrim is
                                 needed) rather than overlaid on the photo — the overlaid treatment
                                 only starts fitting once the panel is wide enough to keep it clear of
                                 the vehicle, which is also where the desktop-only copy below lives. --}}
                            <div class="lg:flex lg:items-end lg:justify-between lg:gap-10">
                                <div class="lg:max-w-2xl">
                                    <x-blocks.partials.fleet-truck-info :item="$item" />
                                </div>
                                <x-ui.button
                                    :href="$item['ask']['href']"
                                    tone="dark"
                                    icon="arrow-right"
                                    class="group hidden shrink-0 lg:inline-flex"
                                    :target="$item['ask']['external'] ? '_blank' : null"
                                    :rel="$item['ask']['external'] ? 'noopener' : null"
                                >{{ $item['ask']['label'] }}<span class="sr-only">: {{ $item['name'] }}</span></x-ui.button>
                            </div>

                            {{-- Box matches the source photos' native ratio (both are ~16:9: FE71
                                 1600x900 exactly, FE74 1672x941 ~= 16:9) instead of the old 75:35
                                 (15:7 = 2.14, meaningfully wider than the source's 1.78) — that gap
                                 was exactly why object-cover was cropping top/bottom, swallowing the
                                 cabin and wheels: cover scales the image up until it fills the box's
                                 width, and whatever wider-aspect box demands gets made up by cropping
                                 the excess height. Matching the ratio means near-zero crop either way,
                                 so the full vehicle — cabin, box, wheels, brand — reads as one object.
                                 fleet-hero-panel (not the light studio-panel gradient used by the grid
                                 layout below): a dark placeholder so nothing here ever flashes as a
                                 pale card against the section's dark background. --}}
                            <div class="fleet-hero-panel relative mt-4 aspect-[16/9] overflow-hidden rounded-2xl lg:mt-6 lg:aspect-[11/5]">
                                @if ($image = $item['image'])
                                    <img
                                        src="{{ $image['small']['url'] }}"
                                        srcset="{{ \App\Support\MediaPresenter::srcset($image) }}"
                                        sizes="(min-width: 1280px) 1232px, 100vw"
                                        width="{{ $image['large']['width'] }}"
                                        height="{{ $image['large']['height'] }}"
                                        alt="{{ $item['name'] }}"
                                        loading="lazy"
                                        decoding="async"
                                        class="absolute inset-0 size-full object-cover"
                                        style="object-position: {{ $item['focus'] }}; transform: scale({{ $item['zoom'] }}); transform-origin: {{ $item['focus'] }}"
                                    >
                                @endif

                                {{-- Edge vignette (see CSS): darkens only toward the panel's corners,
                                     transparent through the middle where the truck sits, so the photo's
                                     own light studio backdrop melts into the dark section instead of
                                     reading as a pale rectangle pasted on the page. --}}
                                <div class="pointer-events-none absolute inset-0 fleet-vignette"></div>

                                {{-- Arrows live inside each slide's own image box (not the outer
                                     wrapper), so they stay positioned on the photo itself. Only the
                                     active slide is on-screen (the rest are translated away and
                                     inert), so only one pair is ever seen. Bottom corners (not vertical
                                     centre): centred arrows sat mid-body on every one of these side-view
                                     trucks, right across the cargo box — bottom corners land past the
                                     wheels instead, out of the vehicle's own visual mass. Thin outlined
                                     circles, no solid fill: they read as a quiet hero control, not an
                                     oversized e-commerce carousel button. Same position at every
                                     breakpoint — nothing else in the panel needs clearing any more. --}}
                                @if ($count > 1)
                                    <button type="button" data-nav="prev" @click="prev()" class="js-only absolute bottom-4 left-4 z-10 grid size-8 place-items-center rounded-full border border-white/30 bg-overlay/30 text-white/80 backdrop-blur-sm transition hover:-translate-x-0.5 hover:border-white hover:bg-overlay/70 hover:text-white focus-visible:border-white focus-visible:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-highlight sm:size-9">
                                        <x-ui.icon name="arrow-right" class="size-3.5 rotate-180" />
                                        <span class="sr-only">{{ __('Previous truck') }}</span>
                                    </button>
                                    <button type="button" data-nav="next" @click="next()" class="js-only absolute right-4 bottom-4 z-10 grid size-8 place-items-center rounded-full border border-white/30 bg-overlay/30 text-white/80 backdrop-blur-sm transition hover:translate-x-0.5 hover:border-white hover:bg-overlay/70 hover:text-white focus-visible:border-white focus-visible:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-highlight sm:size-9">
                                        <x-ui.icon name="arrow-right" class="size-3.5" />
                                        <span class="sr-only">{{ __('Next truck') }}</span>
                                    </button>
                                @endif
                            </div>

                            {{-- Mobile only: the CTA after the image (see content-order note above).
                                 Desktop's copy lives inside the overlay, right after the description. --}}
                            <div class="mt-4 lg:hidden">
                                <x-ui.button
                                    :href="$item['ask']['href']"
                                    tone="dark"
                                    icon="arrow-right"
                                    class="group w-full justify-center sm:w-auto sm:justify-start"
                                    :target="$item['ask']['external'] ? '_blank' : null"
                                    :rel="$item['ask']['external'] ? 'noopener' : null"
                                >{{ $item['ask']['label'] }}<span class="sr-only">: {{ $item['name'] }}</span></x-ui.button>
                            </div>

                            {{-- Technical spec strip: replaces the old floating white card
                                 (desktop, absolutely positioned over the photo) and the full spec
                                 card (mobile, in flow). Same underlying $item['specs']/notes data,
                                 same conditional label rendering (a truck missing a field just
                                 drops that column) — just a thin typographic bar instead of a
                                 shadowed white tile, shown once per truck at every breakpoint
                                 instead of two breakpoint-specific copies of the same information. --}}
                            @if ($item['specs'])
                                <dl class="mt-5 flex flex-wrap gap-x-6 gap-y-3 border-t border-white/10 pt-4 sm:gap-x-10">
                                    @foreach ($item['specs'] as $spec)
                                        <div class="min-w-28">
                                            <dt class="text-xs font-semibold tracking-wide text-white/50 uppercase">{{ $spec['label'] }}</dt>
                                            <dd class="mt-1 font-bold tabular-nums text-white">{{ $spec['value'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                            @if ($item['notes'])
                                <p class="mt-3 text-xs text-white/50">{{ $item['notes'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Fleet selector: a numbered list (01/02…), not a thumbnail gallery — the
                 photo is already the hero image above, so a row of small duplicate crops
                 added nothing but visual noise. Same tablist/tab roles, ids and Alpine
                 wiring as before, restyled only. --}}
            @if ($count > 1)
                <div class="js-only scrollbar-none mt-6 flex items-center gap-6 overflow-x-auto border-t border-white/10 pt-4 sm:gap-8" role="tablist" aria-label="{{ $block['title'] ?? __('Fleet') }}" x-ref="tabs" @keydown="onTabKey($event)">
                    @foreach ($items as $i => $item)
                        <button
                            type="button"
                            id="{{ $block['id'] }}-tab-{{ $i }}"
                            role="tab"
                            aria-controls="{{ $block['id'] }}-truck-{{ $i }}"
                            x-bind:aria-selected="(index === {{ $i }}).toString()"
                            x-bind:tabindex="index === {{ $i }} ? 0 : -1"
                            @click="go({{ $i }})"
                            class="shrink-0 rounded-sm text-left whitespace-nowrap transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                        >
                            <span class="flex items-baseline gap-2 border-b-2 pb-3 transition-colors" :class="index === {{ $i }} ? 'border-highlight' : 'border-transparent'">
                                <span class="text-xs font-semibold tabular-nums transition-colors" :class="index === {{ $i }} ? 'text-highlight' : 'text-white/35'">{{ sprintf('%02d', $i + 1) }}</span>
                                <span class="text-sm font-bold tracking-tight transition-colors sm:text-base" :class="index === {{ $i }} ? 'text-white' : 'text-white/45 hover:text-white/70'">{{ $item['name'] }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <div class="mt-10 sm:mt-12" x-data="{ type: 'all' }">
            @if (count($block['types']) > 1)
                <div class="js-only mb-6 flex gap-2" role="group" aria-label="{{ __('Filter') }}">
                    @foreach (['all' => __('All'), 'reefer' => __('Refrigerated'), 'dry' => __('Dry')] as $value => $label)
                        <button type="button" @click="type = '{{ $value }}'" x-bind:aria-pressed="(type === '{{ $value }}').toString()" class="rounded-full border px-4 py-1.5 text-sm font-semibold transition" :class="type === '{{ $value }}' ? 'border-brand bg-brand text-white' : 'border-white/20 hover:border-brand'">{{ $label }}</button>
                    @endforeach
                </div>
            @endif
            <ul class="grid gap-6 md:grid-cols-2 xl:grid-cols-3" role="list">
                @foreach ($items as $item)
                    <li x-show="type === 'all' || type === '{{ $item['type'] }}'" class="overflow-hidden rounded-card border border-white/15 bg-white/5">
                        <div class="studio-panel aspect-[16/9]">
                            @if ($image = $item['image'])
                                <img src="{{ $image['small']['url'] }}" srcset="{{ \App\Support\MediaPresenter::srcset($image) }}" sizes="(min-width: 1280px) 400px, (min-width: 768px) 50vw, 100vw" width="{{ $image['large']['width'] }}" height="{{ $image['large']['height'] }}" alt="{{ $item['name'] }}" loading="lazy" class="size-full object-cover" style="object-position: {{ $item['focus'] }}">
                            @endif
                        </div>
                        <div class="p-6">
                            <span class="text-xs font-semibold text-brand">{{ $item['typeLabel'] }}</span>
                            <h3 class="mt-1 text-xl font-bold">{{ $item['name'] }}</h3>
                            <dl class="mt-4 space-y-1.5 text-sm">
                                @foreach ($item['specs'] as $spec)
                                    <div class="flex justify-between gap-4"><dt class="text-white/60">{{ $spec['label'] }}</dt><dd class="font-semibold tabular-nums">{{ $spec['value'] }}</dd></div>
                                @endforeach
                            </dl>
                            <a href="{{ $item['ask']['href'] }}" @if ($item['ask']['external']) target="_blank" rel="noopener" @endif class="mt-5 inline-flex items-center gap-1.5 font-semibold text-brand hover:underline">{{ $item['ask']['label'] }}<span class="sr-only">: {{ $item['name'] }}</span><x-ui.icon name="arrow-right" class="size-4" /></a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-ui.section>
