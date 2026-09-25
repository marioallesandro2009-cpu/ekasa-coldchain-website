{{--
    Home page services (#services): heading (splitText) from "Section Beranda", a grid of
    the main (Utama) services as cards (4:3 photo, icon, name, description, up to three
    key points, link), and the supporting (Pendukung) services as ribbons below.
    Cards: spotlightCard, each fading in once with animatedContent. The whole card is
    one link (stretched over the card) to the detail page, or to #contact while the
    detail page is not published.
--}}
@props(['section', 'services', 'texts'])
@php
    $featured = array_values(array_filter($services, fn (array $service): bool => $service['featured']));
    $supporting = array_values(array_filter($services, fn (array $service): bool => ! $service['featured']));
    $dark = $section['background'] === 'dark';
    $contact = '#'.\App\Models\PageSection::CONTACT;
@endphp
<x-ui.section
    :id="$section['id']"
    :variant="$section['background']"
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

    @if ($featured)
        <ul @class([
            'mt-10 grid gap-6 sm:mt-12 md:grid-cols-2',
            'xl:grid-cols-3' => count($featured) > 4,
        ]) role="list">
            @foreach ($featured as $i => $service)
                <li x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="{{ ($i % 2) * 0.1 + intdiv($i, 2) * 0.05 }}">
                    <article
                        x-data="spotlightCard"
                        class="spotlight-card is-light group relative flex h-full flex-col overflow-hidden rounded-card border border-ink/10 bg-white text-ink has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-2 has-[a:focus-visible]:outline-brand"
                    >
                        <div class="relative aspect-[4/3] overflow-hidden bg-surface">
                            @if ($image = $service['image'])
                                <img
                                    src="{{ $image['small']['url'] }}"
                                    srcset="{{ \App\Support\MediaPresenter::srcset($image) }}"
                                    sizes="(min-width: 1280px) 616px, (min-width: 768px) 50vw, 100vw"
                                    width="{{ $image['large']['width'] }}"
                                    height="{{ $image['large']['height'] }}"
                                    alt=""
                                    loading="lazy"
                                    decoding="async"
                                    style="object-position: {{ $image['focus'] }}"
                                    class="absolute inset-0 size-full object-cover"
                                >
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-6 sm:p-7">
                            <div class="flex items-center gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand/10 text-brand">
                                    <x-ui.icon :name="$service['icon']" class="size-5" />
                                </span>
                                <h3 class="text-xl leading-snug font-bold">
                                    <a href="{{ $service['url'] ?? $contact }}" class="focus-visible:outline-none after:absolute after:inset-0 after:content-['']">{{ $service['title'] }}</a>
                                </h3>
                            </div>
                            @if ($service['description'])
                                <p class="mt-3 text-ink-muted">{{ $service['description'] }}</p>
                            @endif
                            @if ($service['keyPoints'])
                                <ul class="mt-4 space-y-2 text-[15px]" role="list">
                                    @foreach ($service['keyPoints'] as $point)
                                        <li class="flex items-start gap-2.5">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 size-4.5 shrink-0 text-brand" aria-hidden="true" focusable="false"><path d="M5 12.5 9.5 17 19 7.5"/></svg>
                                            <span>{{ $point }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            <span class="mt-auto inline-flex items-center gap-1.5 pt-6 font-semibold text-brand" aria-hidden="true">
                                {{ $texts['service_card_link'] }}
                                <x-ui.icon name="arrow-right" class="size-4 transition-transform duration-200 group-hover:translate-x-0.5" />
                            </span>
                        </div>
                    </article>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($supporting)
        <ul class="mt-6 space-y-4" role="list">
            @foreach ($supporting as $service)
                <li x-data="animatedContent" x-intersect.once="show()" data-animate>
                    <a
                        href="{{ $service['url'] ?? $contact }}"
                        @class([
                            'group flex flex-col gap-4 rounded-card border p-5 transition-colors duration-200 sm:flex-row sm:items-center sm:gap-5 sm:p-6',
                            'border-white/15 bg-white/5 hover:bg-white/10' => $dark,
                            'border-ink/10 bg-white hover:border-brand/30' => ! $dark,
                        ])
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
    @endif
</x-ui.section>
