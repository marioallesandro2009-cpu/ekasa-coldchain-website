{{--
    Services index (/services, /id/layanan): every active, published service as a
    bento grid — unbounded (unlike the home teaser and navbar mega menu, which cap
    to Service::MAX_FEATURED; see ServiceIndexController). At lg+ the whole page
    (navbar + intro + grid) fits one viewport (no scroll); on phones it scrolls
    normally. Background: a very faint hexagonal dot field (echoes the Ekasa logo
    hexagon and the lokasi_gudang hex map) plus a dashed connector with a dot per
    card for the first 4 ("4 pillars, one connected system" — a fixed design
    motif, not meant to track an arbitrary count), plain dashed line (no dots)
    for any beyond that — vertical on phones, following the bento shape (branch
    into a spine) at lg+.
    Content is entirely admin data (Service::active, is_featured, sort_order);
    the bento math (lead + up to 3 side + N more in extra rows) already scales
    from 1 to 10+ services without a rewrite — same math as the home page teaser
    (sections.services), which is naturally capped at 4 by featuredServices so
    its own "more" row never fires.
--}}
@php
    $featured = array_values(array_filter($services, fn (array $s): bool => $s['featured']));
    $supporting = array_values(array_filter($services, fn (array $s): bool => ! $s['featured']));
    $lead = $featured[0] ?? null;
    $side = array_slice($featured, 1, 3);
    $more = array_slice($featured, 4);
    $total = count($featured);
    $dotCount = min($total, \App\Models\Service::MAX_FEATURED);
    $leadClass = match (count($side)) {
        0 => 'lg:col-span-12',
        1 => 'lg:col-span-7',
        2 => 'lg:col-span-7 lg:row-span-2',
        default => 'lg:col-span-7 lg:row-span-3',
    };
    $section = collect($sections)->firstWhere('id', \App\Models\PageSection::SERVICES);
    $texts = $settings['texts'];
    $home = \App\Support\Locales::path(\App\Support\Locales::current());
@endphp
<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">
    <x-layout.navbar :settings="$settings" :services="$services" :featured-services="$featuredServices" :sections="$sections" solid />

    <main id="main">
        {{-- The "fits in one viewport, no scroll" treatment (lg:h-svh + overflow-hidden)
             only makes sense for the original 4-or-fewer bento shape — once a "more" row
             exists (5th+ featured service), forcing a fixed viewport height would clip it
             instead of scaling, so it drops to normal scrolling flow like the ribbon below. --}}
        <div @class(['hex-dot-field relative isolate lg:flex lg:flex-col lg:pt-20', 'lg:h-svh lg:overflow-hidden' => ! $more])>
            <section class="container-site relative flex-none pt-24 pb-6 lg:pt-8 lg:pb-6">
                <nav aria-label="{{ __('Breadcrumb') }}">
                    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-ink-muted">
                        <li><a href="{{ $home }}" class="rounded hover:text-brand">{{ __('Home') }}</a></li>
                        <li aria-hidden="true">/</li>
                        <li aria-current="page" class="font-semibold text-ink">{{ __('Services') }}</li>
                    </ol>
                </nav>

                <div class="mt-4 max-w-2xl" x-data="animatedContent" x-intersect.once="show()" data-animate>
                    @if ($section['eyebrow'] ?? null)
                        <p class="text-sm font-bold tracking-[0.14em] text-brand uppercase">{{ $section['eyebrow'] }}</p>
                    @endif
                    <h1
                        id="services-index-title"
                        x-data="splitText"
                        data-split-text="h1"
                        @class(['text-[clamp(1.75rem,1.1rem+2.4vw,2.75rem)] leading-[1.05] font-extrabold tracking-[-0.02em] text-balance', 'mt-2' => $section['eyebrow'] ?? null])
                    >{{ $section['title'] ?? __('Services') }}</h1>
                    @if ($section['description'] ?? null)
                        <p class="mt-2 max-w-xl leading-relaxed text-ink-muted">{{ $section['description'] }}</p>
                    @endif
                </div>
            </section>

            <section class="container-site relative flex-1 pb-10 lg:min-h-0 lg:pb-8" aria-labelledby="services-index-title">
                {{-- Connector line: the full height of this section (bento block + any
                     "more" row below it), left edge on phones (single column), the
                     lead/side gutter at lg+ (58.33% = the 7/12 column boundary the bento
                     grid below uses). Dots are a separate box below, sized to the bento
                     block alone — not this one — so they can't drift once a "more" row
                     makes this section taller than that block. --}}
                @if ($total > 1)
                    <div class="connector pointer-events-none absolute inset-y-6 left-4 lg:inset-y-2 lg:left-[58.3333%]" aria-hidden="true">
                        <span class="connector-line absolute inset-y-0 left-0 border-l-2 border-dashed"></span>
                    </div>
                @endif

                @if ($lead)
                    <div class="relative h-full">
                        {{-- One dot per card for the first {{ \App\Models\Service::MAX_FEATURED }}
                             only ("4 pillars" is a fixed motif, not a per-card counter) — sized
                             to this bento block's own height, so it stays accurate regardless of
                             what the "more" row below adds to the section's total height. --}}
                        @if ($total > 1)
                            <div class="pointer-events-none absolute inset-y-0 left-4 lg:left-[58.3333%]" aria-hidden="true">
                                @for ($i = 0; $i < $dotCount; $i++)
                                    <span class="connector-dot absolute -left-[5px] size-2.5 rounded-full bg-brand" style="top: {{ ($i + 0.5) / $dotCount * 100 }}%"></span>
                                @endfor
                            </div>
                        @endif

                        <ul class="grid h-full gap-4 sm:gap-5 lg:grid-cols-12 lg:[grid-auto-rows:1fr]" role="list">
                            <x-sections.service-card-index :service="$lead" :number="1" size="large" :texts="$texts" :class="$leadClass" />
                            @foreach ($side as $i => $service)
                                <x-sections.service-card-index :service="$service" :number="$i + 2" :texts="$texts" :delay="0.1 + $i * 0.08" class="lg:col-span-5" />
                            @endforeach
                        </ul>
                    </div>

                    @if ($more)
                        <ul class="mt-4 grid gap-4 sm:mt-5 sm:gap-5 lg:grid-cols-12" role="list">
                            @foreach ($more as $i => $service)
                                <x-sections.service-card-index :service="$service" :number="$i + 5" :texts="$texts" :delay="$i * 0.08" class="lg:col-span-4" />
                            @endforeach
                        </ul>
                    @endif
                @endif
            </section>
        </div>

        {{-- Non-featured services: normal scrollable flow below the 1-viewport
             hero+grid, at every breakpoint — the 1-viewport requirement only
             covers the navbar+intro+featured grid. --}}
        @if ($supporting)
            <section class="container-site relative pb-16">
                {{-- Continues the connector above into this ribbon, as one unbroken path
                     (zero gap, zero overlap: the h-6/-top-6 and lg:h-7/-top-2 here exactly
                     cancel the other connector's own bottom inset, inset-y-6 / lg:inset-y-2).
                     Phones/tablets keep the same straight line (single column, nothing to
                     aim at). At lg+ it bends into a rounded elbow that lands exactly on the
                     first ribbon card's icon ("Also Available") — left-11/top-5 is that
                     icon's fixed offset from the card's edge (sm:p-6 padding + half of its
                     size-10 badge) — instead of a stub that floats past it. --}}
                @if ($total > 1)
                    <div class="connector-tail pointer-events-none absolute -top-6 left-4 h-6 lg:hidden" aria-hidden="true">
                        <span class="connector-line absolute inset-y-0 left-0 border-l-2 border-dashed"></span>
                        <span class="connector-dot absolute -left-[5px] bottom-0 size-2.5 rounded-full bg-brand"></span>
                    </div>
                    <div
                        class="connector-line pointer-events-none absolute -top-2 right-[41.6667%] left-11 hidden h-7 rounded-br-2xl border-r-2 border-b-2 border-dashed lg:block"
                        aria-hidden="true"
                    ></div>
                    <span
                        class="connector-dot pointer-events-none absolute top-5 left-11 hidden size-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand lg:block"
                        aria-hidden="true"
                    ></span>
                @endif

                <ul class="space-y-4" role="list">
                    @foreach ($supporting as $service)
                        <li x-data="animatedContent" x-intersect.once="show()" data-animate>
                            <a
                                href="{{ $service['url'] ?? '#'.\App\Models\PageSection::CONTACT }}"
                                class="group flex flex-col gap-4 rounded-card border border-ink/10 bg-white p-5 transition-colors duration-200 hover:border-brand/30 sm:flex-row sm:items-center sm:gap-5 sm:p-6"
                            >
                                <span class="flex items-center gap-3 sm:w-56 sm:shrink-0">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand/10 text-brand">
                                        <x-ui.icon :name="$service['icon']" class="size-5" />
                                    </span>
                                    <span class="text-xs font-bold tracking-[0.12em] text-ink-muted uppercase">{{ $texts['service_supporting_label'] }}</span>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-lg font-bold">{{ $service['title'] }}</span>
                                    @if ($service['description'])
                                        <span class="mt-0.5 block text-ink-muted">{{ $service['description'] }}</span>
                                    @endif
                                </span>
                                <x-ui.icon name="arrow-right" class="size-5 shrink-0 text-brand transition-transform duration-200 group-hover:translate-x-0.5" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </main>

    <x-layout.footer :settings="$settings" />
</x-layouts::app>
