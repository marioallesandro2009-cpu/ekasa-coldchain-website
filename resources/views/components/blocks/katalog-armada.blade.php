{{--
    Fleet catalogue. Showcase (default): one truck at a time, name, type, description
    and spec bars (relative to the largest value, empty fields hidden) on the left, the
    side view on a grey studio panel on the right (display crop from the admin), then
    thumbnails (tabs) and previous/next. Grid: one card per truck, with a
    Semua/Reefer/Dry filter when both types exist. Without JS: every truck, listed.
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
            <div class="overflow-hidden" @pointerdown="pointerDown($event)" @pointerup="pointerUp($event)">
                <div class="showcase-track flex flex-col gap-16" :style="trackStyle()">
                    @foreach ($items as $i => $item)
                        <div
                            id="{{ $block['id'] }}-truck-{{ $i }}"
                            class="showcase-slide grid items-center gap-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-12"
                            :class="{ 'is-active': index === {{ $i }} }"
                            role="tabpanel"
                            aria-labelledby="{{ $block['id'] }}-tab-{{ $i }}"
                            x-bind:aria-hidden="(index !== {{ $i }}).toString()"
                            x-bind:inert="index !== {{ $i }}"
                            x-data="animatedContent" x-intersect.once="show()" data-draw
                        >
                            <div class="order-2 lg:order-1">
                                <span class="inline-flex rounded-full bg-brand/10 px-3 py-1 text-xs font-semibold text-brand">{{ $item['typeLabel'] }}</span>
                                <h3 class="mt-3 text-3xl font-extrabold tracking-tight">{{ $item['name'] }}</h3>
                                @if ($item['description'])
                                    <p class="mt-3 text-ink-muted">{{ $item['description'] }}</p>
                                @endif
                                @if ($item['specs'])
                                    <dl class="mt-6 space-y-4">
                                        @foreach ($item['specs'] as $spec)
                                            <div>
                                                <div class="flex items-baseline justify-between gap-4 text-sm">
                                                    <dt class="text-ink-muted">{{ $spec['label'] }}</dt>
                                                    <dd class="font-bold tabular-nums">{{ $spec['value'] }}</dd>
                                                </div>
                                                @if ($spec['bar'] !== null)
                                                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-ink/10" aria-hidden="true">
                                                        <div class="spec-bar h-full rounded-full bg-brand" style="width: {{ $spec['bar'] }}%"></div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif
                                @if ($item['notes'])
                                    <p class="mt-4 text-sm text-ink-muted">{{ $item['notes'] }}</p>
                                @endif
                                <x-ui.button
                                    :href="$item['ask']['href']"
                                    tone="light"
                                    icon="arrow-right"
                                    class="group mt-7"
                                    :target="$item['ask']['external'] ? '_blank' : null"
                                    :rel="$item['ask']['external'] ? 'noopener' : null"
                                >{{ $item['ask']['label'] }}<span class="sr-only">: {{ $item['name'] }}</span></x-ui.button>
                            </div>
                            <div class="studio-panel order-1 flex max-h-[50vh] items-center justify-center overflow-hidden rounded-2xl lg:order-2" style="aspect-ratio: 16 / 9">
                                @if ($image = $item['image'])
                                    <img
                                        src="{{ $image['small']['url'] }}"
                                        srcset="{{ \App\Support\MediaPresenter::srcset($image) }}"
                                        sizes="(min-width: 1280px) 720px, (min-width: 1024px) 58vw, 100vw"
                                        width="{{ $image['large']['width'] }}"
                                        height="{{ $image['large']['height'] }}"
                                        alt="{{ $item['name'] }}"
                                        loading="lazy"
                                        decoding="async"
                                        class="size-full object-cover"
                                        style="object-position: {{ $item['focus'] }}; transform: scale({{ $item['zoom'] }}); transform-origin: {{ $item['focus'] }}"
                                    >
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($count > 1)
                <div class="js-only mt-8 flex items-center justify-between gap-4">
                    <div class="flex gap-3" role="tablist" aria-label="{{ $block['title'] ?? __('Fleet') }}" x-ref="tabs" @keydown="onTabKey($event)">
                        @foreach ($items as $i => $item)
                            <button
                                type="button"
                                id="{{ $block['id'] }}-tab-{{ $i }}"
                                role="tab"
                                aria-controls="{{ $block['id'] }}-truck-{{ $i }}"
                                x-bind:aria-selected="(index === {{ $i }}).toString()"
                                x-bind:tabindex="index === {{ $i }} ? 0 : -1"
                                @click="go({{ $i }})"
                                class="studio-panel h-16 w-28 overflow-hidden rounded-lg ring-2 ring-transparent transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand sm:h-20 sm:w-36"
                                :class="index === {{ $i }} ? 'ring-brand!' : 'opacity-70 hover:opacity-100'"
                            >
                                @if ($image = $item['image'])
                                    <img src="{{ $image['small']['url'] }}" alt="" width="{{ $image['small']['width'] }}" height="{{ $image['small']['height'] }}" loading="lazy" class="size-full object-cover" style="object-position: {{ $item['focus'] }}">
                                @endif
                                <span class="sr-only">{{ $item['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="prev()" class="grid size-11 place-items-center rounded-full border border-ink/15 hover:border-brand hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            <x-ui.icon name="arrow-right" class="size-5 rotate-180" />
                            <span class="sr-only">{{ __('Previous truck') }}</span>
                        </button>
                        <button type="button" @click="next()" class="grid size-11 place-items-center rounded-full border border-ink/15 hover:border-brand hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            <x-ui.icon name="arrow-right" class="size-5" />
                            <span class="sr-only">{{ __('Next truck') }}</span>
                        </button>
                    </div>
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
