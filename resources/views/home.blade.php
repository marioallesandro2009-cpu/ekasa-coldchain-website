<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">
    <x-layout.navbar :settings="$settings" :services="$services" :sections="$sections" />

    <main id="main">
        <x-sections.hero :settings="$settings" :slides="$heroSlides" :stats="$heroStats" :trust-logos="$trustLogos" />

        {{-- Sections (texts, order, background: admin "Section Beranda"); content comes in later stages.
             Below the first screen: their Alpine components start near the viewport (x-ignore +
             data-alpine-defer, see app.js), not during page load. --}}
        @foreach ($sections as $section)
            @if ($section['id'] === \App\Models\PageSection::SERVICES)
                <x-sections.services :section="$section" :services="$services" :texts="$settings['texts']" />
                @continue
            @endif
            <x-ui.section :id="$section['id']" :variant="$section['background']" class="flex min-h-screen items-center" aria-labelledby="{{ $section['id'] }}-title" x-ignore data-alpine-defer>
                <x-ui.section-heading
                    :id="$section['id'].'-title'"
                    :eyebrow="$section['eyebrow']"
                    :title="$section['title']"
                    :description="$section['description']"
                    align="center"
                    :tone="$section['background'] === 'dark' ? 'dark' : 'light'"
                />
            </x-ui.section>
        @endforeach
    </main>

    <x-layout.footer :settings="$settings" />
</x-layouts::app>
