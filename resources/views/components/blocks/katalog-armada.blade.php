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
--}}
@props(['block', 'service', 'settings', 'first' => false])
@php
    $items = $block['items'];
    $count = count($items);
    $announce = collect($items)->values()->map(fn (array $item, int $i): string => __('Truck :current of :total: :name', ['current' => $i + 1, 'total' => $count, 'name' => $item['name']]))->all();
@endphp
<x-ui.section :id="$block['id']" :variant="$block['variant']" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <x-ui.section-heading split :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title'] ?? $service['title']" :description="$block['description']" />

    @if ($block['layout'] === 'showcase')
        <div
            class="mt-10 sm:mt-12"
            x-data="fleetShowcase({ count: {{ $count }}, announce: @js($announce) })"
        >
            <p class="sr-only" aria-live="polite" x-text="message"></p>

            <div class="relative overflow-hidden rounded-2xl" @pointerdown="pointerDown($event)" @pointerup="pointerUp($event)">
                <div class="showcase-track flex flex-col gap-10 lg:gap-14" :style="trackStyle()">
                    @foreach ($items as $i => $item)
                        <div
                            id="{{ $block['id'] }}-truck-{{ $i }}"
                            class="showcase-slide"
                            role="tabpanel"
                            aria-labelledby="{{ $block['id'] }}-tab-{{ $i }}"
                            x-bind:aria-hidden="(index !== {{ $i }}).toString()"
                            x-bind:inert="index !== {{ $i }}"
                            x-data="animatedContent" x-intersect.once="show()" data-draw
                        >
                            <div class="studio-panel relative aspect-[16/10] overflow-hidden rounded-2xl lg:aspect-[15/7]">
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

                                {{-- Name + type badge, top-left. The scrim stays flat/dark through the
                                     whole text zone (badge+title+2-line description) and only fades in
                                     the trailing padding below the text, measured against the actual
                                     rendered pixels. Text itself is fully opaque, not relying on shadow.
                                     max-w below lg keeps the wrapped description from reaching the
                                     right edge, where the vertically-centred nav arrow sits on the
                                     shorter mobile image box — otherwise its round white background
                                     can land under the last line of text. --}}
                                <div class="pointer-events-none absolute inset-x-0 top-0 max-w-[calc(100%-4rem)] bg-linear-to-b from-overlay/92 via-65% via-overlay/92 to-transparent p-5 pb-16 sm:p-7 sm:pb-20 lg:max-w-[60%]">
                                    {{-- bg-overlay/50 (not bg-white/10): a lighter pill backdrop lifts
                                         the highlight green just enough to fail contrast against it. --}}
                                    <span @class([
                                        'inline-flex rounded-full border border-white/15 bg-overlay px-2.5 py-0.5 text-xs font-semibold',
                                        'text-highlight' => $item['type'] === 'reefer',
                                        'text-white' => $item['type'] !== 'reefer',
                                    ])>{{ $item['typeLabel'] }}</span>
                                    <h3 class="mt-2 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $item['name'] }}</h3>
                                    @if ($item['description'])
                                        <p class="mt-1.5 line-clamp-2 max-w-md text-sm font-medium text-white sm:text-base">{{ $item['description'] }}</p>
                                    @endif
                                </div>

                                {{-- Spec card: floating over the photo on desktop only. --}}
                                <x-blocks.partials.fleet-spec-card :item="$item" class="hidden lg:absolute lg:top-6 lg:right-6 lg:block lg:w-72" />

                                {{-- Arrows live inside each slide's own image box (not the outer wrapper),
                                     so they stay centred on the photo whatever the mobile spec card below
                                     adds to the slide's total height. Only the active slide is on-screen
                                     (the rest are translated away and inert), so only one pair is ever seen.
                                     Below lg the image is short enough that a true vertical centre lands
                                     on the top-left title/description block, so they sit near the bottom
                                     edge there instead (clear of the text) and only centre once the panel
                                     is tall enough (lg:aspect-[15/7] at typical desktop widths) to leave
                                     both zones their own space. --}}
                                @if ($count > 1)
                                    <button type="button" data-nav="prev" @click="prev()" class="js-only absolute bottom-4 left-3 z-10 grid size-9 place-items-center rounded-full bg-white/80 text-ink opacity-80 shadow-md backdrop-blur transition hover:bg-white hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand sm:bottom-6 sm:left-4 lg:top-1/2 lg:bottom-auto lg:-translate-y-1/2 lg:size-12">
                                        <x-ui.icon name="arrow-right" class="size-4 rotate-180 lg:size-5" />
                                        <span class="sr-only">{{ __('Previous truck') }}</span>
                                    </button>
                                    <button type="button" data-nav="next" @click="next()" class="js-only absolute bottom-4 right-3 z-10 grid size-9 place-items-center rounded-full bg-white/80 text-ink opacity-80 shadow-md backdrop-blur transition hover:bg-white hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand sm:bottom-6 sm:right-4 lg:top-1/2 lg:bottom-auto lg:-translate-y-1/2 lg:size-12">
                                        <x-ui.icon name="arrow-right" class="size-4 lg:size-5" />
                                        <span class="sr-only">{{ __('Next truck') }}</span>
                                    </button>
                                @endif
                            </div>

                            {{-- Spec card again on phones: below the photo, in normal flow, never covering the truck. --}}
                            <x-blocks.partials.fleet-spec-card :item="$item" class="mt-4 block lg:hidden" />
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($count > 1)
                <div class="js-only scrollbar-none mt-5 flex gap-3 overflow-x-auto" role="tablist" aria-label="{{ $block['title'] ?? __('Fleet') }}" x-ref="tabs" @keydown="onTabKey($event)">
                    @foreach ($items as $i => $item)
                        <button
                            type="button"
                            id="{{ $block['id'] }}-tab-{{ $i }}"
                            role="tab"
                            aria-controls="{{ $block['id'] }}-truck-{{ $i }}"
                            x-bind:aria-selected="(index === {{ $i }}).toString()"
                            x-bind:tabindex="index === {{ $i }} ? 0 : -1"
                            @click="go({{ $i }})"
                            class="w-32 shrink-0 rounded-lg text-left transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand sm:w-40"
                        >
                            <span class="studio-panel block aspect-[15/7] overflow-hidden rounded-lg ring-2 ring-transparent" :class="index === {{ $i }} ? 'ring-brand!' : 'opacity-70 hover:opacity-100'">
                                @if ($image = $item['image'])
                                    <img src="{{ $image['small']['url'] }}" alt="" width="{{ $image['small']['width'] }}" height="{{ $image['small']['height'] }}" loading="lazy" class="size-full object-cover" style="object-position: {{ $item['focus'] }}">
                                @endif
                            </span>
                            <span class="mt-1.5 block truncate text-sm font-semibold" :class="index === {{ $i }} ? 'text-brand' : 'text-ink-muted'">{{ $item['name'] }}</span>
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
                        <button type="button" @click="type = '{{ $value }}'" x-bind:aria-pressed="(type === '{{ $value }}').toString()" class="rounded-full border px-4 py-1.5 text-sm font-semibold transition" :class="type === '{{ $value }}' ? 'border-brand bg-brand text-white' : 'border-ink/15 hover:border-brand'">{{ $label }}</button>
                    @endforeach
                </div>
            @endif
            <ul class="grid gap-6 md:grid-cols-2 xl:grid-cols-3" role="list">
                @foreach ($items as $item)
                    <li x-show="type === 'all' || type === '{{ $item['type'] }}'" @class(['overflow-hidden rounded-card border border-ink/10', 'bg-white' => $block['variant'] === 'surface', 'bg-surface' => $block['variant'] !== 'surface'])>
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
                                    <div class="flex justify-between gap-4"><dt class="text-ink-muted">{{ $spec['label'] }}</dt><dd class="font-semibold tabular-nums">{{ $spec['value'] }}</dd></div>
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
