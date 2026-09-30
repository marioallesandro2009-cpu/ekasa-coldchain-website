{{--
    Home page services (#services): heading (splitText) from "Section Beranda", the main
    (Utama) services as a bento grid (the first in admin order as a large 4:5 card with
    key points, the next three as 16:9 cards stacked beside it; one column on phones,
    the large card taller). DOM order = admin order; the grid only places them.
    A pure teaser (Bagian 2, "TAHAP 3" brief): only the shared, capped featuredServices
    (see FrontendData::build() — same set the navbar mega menu uses, so home and navbar
    can never disagree) — no supporting/Pendukung ribbon here at all, unlike the full
    /services index. Every other published service, featured-but-bumped-past-the-cap or
    not, is only ever visible via "View all services" below the grid. Cards are
    sections.service-card-index in "compact" mode (number + title + photo, no
    description/key points), same asymmetric proportions as before.
--}}
@props(['section', 'featuredServices', 'texts'])
@php
    $featured = $featuredServices;
    $dark = $section['background'] === 'dark';
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
            // Bento: the first main service (admin order) as the large card, the rest
            // (at most 3, since $featured is capped to Service::MAX_FEATURED) stacked
            // beside it — no "more" row here, unlike the unbounded /services index.
            $lead = $featured[0];
            $side = array_slice($featured, 1, 3);
            $leadClass = match (count($side)) {
                0 => 'lg:col-span-12',
                1 => 'lg:col-span-7',
                2 => 'lg:col-span-7 lg:row-span-2',
                default => 'lg:col-span-7 lg:row-span-3',
            };
        @endphp
        <ul class="mt-6 grid gap-3 sm:mt-8 sm:gap-4 lg:grid-cols-12" role="list">
            <x-sections.service-card-index :service="$lead" :number="1" size="large" :texts="$texts" compact :class="$leadClass" />
            @foreach ($side as $i => $service)
                <x-sections.service-card-index :service="$service" :number="$i + 2" :texts="$texts" compact :delay="0.1 + $i * 0.08" class="lg:col-span-5" />
            @endforeach
        </ul>

        <div class="mt-5 text-center sm:mt-6">
            <x-ui.button :href="\App\Models\Service::indexPath()" tone="light" icon="arrow-right" class="group">
                {{ __('View all services') }}
            </x-ui.button>
        </div>
    @endif
</x-ui.section>
