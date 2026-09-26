{{--
    Home page services (#services): heading (splitText) from "Section Beranda", the main
    (Utama) services as a bento grid (the first in admin order as a large 4:5 card with
    key points, the next three as 16:9 cards stacked beside it, more in a row below;
    one column on phones, the large card taller), and the supporting (Pendukung)
    services as ribbons below. DOM order = admin order; the grid only places them.
    Cards (sections.service-card): spotlightCard, each fading in once with
    animatedContent, one link to the detail page (or #contact while it is a draft).
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
        @php
            // Bento: the first main service (admin order) as the large card, the next
            // three stacked beside it, any further ones in a row below.
            $lead = $featured[0];
            $side = array_slice($featured, 1, 3);
            $more = array_slice($featured, 4);
            $leadClass = match (count($side)) {
                0 => 'lg:col-span-12',
                1 => 'lg:col-span-7',
                2 => 'lg:col-span-7 lg:row-span-2',
                default => 'lg:col-span-7 lg:row-span-3',
            };
        @endphp
        <ul class="mt-8 grid gap-4 sm:mt-10 sm:gap-5 lg:grid-cols-12" role="list">
            <x-sections.service-card :service="$lead" size="large" :texts="$texts" :class="$leadClass" />
            @foreach ($side as $i => $service)
                <x-sections.service-card :service="$service" :texts="$texts" :delay="0.1 + $i * 0.08" class="lg:col-span-5" />
            @endforeach
            @foreach ($more as $i => $service)
                <x-sections.service-card :service="$service" :texts="$texts" :delay="$i * 0.08" class="lg:col-span-4" />
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
