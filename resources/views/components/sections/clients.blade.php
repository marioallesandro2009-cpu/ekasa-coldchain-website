{{--
    Home page #clients section: every active client logo (App\Models\TrustLogo
    — same admin data as the hero's own capped "Trusted by" row, see
    FrontendData::build()'s trustLogos/clientLogos pair) as a two-row logo
    wall, each row scrolling the opposite direction — ported from React Bits'
    LogoLoop to Alpine (resources/js/alpine/logoLoop.js) + plain CSS
    (.logo-loop*, resources/css/app.css). Two opposite-direction rows (not
    one long strip) read as a fuller wall with only a handful of logos, and
    give the section enough real content to earn its own full-screen height
    instead of a lot of empty space around one thin strip.

    Grayscale by default, full color on hover — the opposite treatment from
    the hero's row (which forces every logo to solid white via brightness-0
    invert, since it always sits over a dark hero photo): this section's own
    background is light ("white" in Bagian Beranda), so a forced-white logo
    would be invisible instead of uniform.

    Below the logo wall: "Industries We Serve" (App\Models\Industry, admin
    "Industri yang Dilayani") as plain numbered editorial rows — no cards, no
    shadows, just a number, a title and a description separated by hairline
    rules, matching the brief's "editorial, not SaaS-card" direction.
--}}
@props(['section', 'clientLogos', 'industries' => []])
@php
    // Split into two rows (not the full list twice) so the second row reads
    // as more logo wall, not a repeat of the first — falls back to showing
    // the same handful in both rows once there are too few to split.
    $half = (int) ceil(count($clientLogos) / 2);
    $rowA = $half > 0 ? array_slice($clientLogos, 0, $half) : $clientLogos;
    $rowB = $half > 0 ? array_slice($clientLogos, $half) : $clientLogos;
    if (! $rowB) {
        $rowB = $rowA;
    }
@endphp
<x-ui.section :id="$section['id']" :variant="$section['background']" class="flex flex-col justify-center" aria-labelledby="{{ $section['id'] }}-title" x-ignore data-alpine-defer>
    <x-ui.section-heading
        split
        align="center"
        :id="$section['id'].'-title'"
        :eyebrow="$section['eyebrow']"
        :title="$section['title']"
        :description="$section['description']"
    />

    @if ($clientLogos)
        @if (count($clientLogos) > 1)
            <p
                class="mt-6 text-center text-sm font-semibold text-ink-muted"
                x-data="animatedContent"
                x-intersect.once="show()"
                data-animate
            >
                <span
                    x-data="countUp({ to: {{ count($clientLogos) }} })"
                    x-intersect.once="start()"
                    class="text-lg font-extrabold text-brand"
                >{{ count($clientLogos) }}</span>+ {{ __('businesses already trust us with their cold chain') }}
            </p>
        @endif

        <div class="mt-10 space-y-6 sm:mt-12 sm:space-y-8">
            <div
                class="logo-loop"
                x-data="logoLoop({ speed: 32 })"
            >
                <div class="logo-loop__track" x-ref="track">
                    @for ($copy = 0; $copy < 2; $copy++)
                        <ul class="logo-loop__list" role="list" @if ($copy === 0) x-ref="sequence" @else aria-hidden="true" @endif>
                            @foreach ($rowA as $item)
                                <li class="logo-loop__item px-6 sm:px-8">
                                    <x-sections.partials.client-logo :item="$item" />
                                </li>
                            @endforeach
                        </ul>
                    @endfor
                </div>
            </div>

            <div
                class="logo-loop"
                x-data="logoLoop({ speed: 26, reverse: true })"
            >
                <div class="logo-loop__track" x-ref="track">
                    @for ($copy = 0; $copy < 2; $copy++)
                        <ul class="logo-loop__list" role="list" @if ($copy === 0) x-ref="sequence" @else aria-hidden="true" @endif>
                            @foreach ($rowB as $item)
                                <li class="logo-loop__item px-6 sm:px-8">
                                    <x-sections.partials.client-logo :item="$item" />
                                </li>
                            @endforeach
                        </ul>
                    @endfor
                </div>
            </div>
        </div>
    @else
        <p class="mt-8 text-center text-ink-muted">Belum ada logo klien. Tambahkan lewat admin: Logo Klien.</p>
    @endif

    @if ($industries)
        <div class="mt-16 border-t border-ink/10 pt-12 sm:mt-20 sm:pt-16">
            <p class="text-center text-sm font-bold tracking-[0.14em] text-brand uppercase">{{ __('Industries We Serve') }}</p>

            <dl class="mt-8 divide-y divide-ink/10 border-y border-ink/10" x-data="animatedList" data-animated-list>
                @foreach ($industries as $i => $industry)
                    <div class="flex flex-col gap-2 py-6 sm:flex-row sm:items-baseline sm:gap-8 sm:py-7">
                        <dt class="flex shrink-0 items-baseline gap-4 sm:w-56">
                            <span class="text-sm font-bold text-brand tabular-nums">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="text-lg font-bold text-ink">{{ $industry['title'] }}</span>
                        </dt>
                        @if ($industry['description'])
                            <dd class="max-w-2xl text-sm leading-relaxed text-ink-muted">{{ $industry['description'] }}</dd>
                        @endif
                    </div>
                @endforeach
            </dl>
        </div>
    @endif
</x-ui.section>
