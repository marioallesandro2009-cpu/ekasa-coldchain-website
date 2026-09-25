{{--
    Full-screen hero. The first photo, the first service label and the final
    statistics are in the HTML; JavaScript adds the slideshow and animations.
    One index (Alpine "hero") drives both the photo and the rotating label.
--}}
@props(['settings', 'slides', 'stats', 'trustLogos' => []])
@php
    use App\Support\MediaPresenter;

    $count = count($slides);
    $labels = array_column($slides, 'label');
    // Characters of the longest label: sizes the label line so it fits on one line (see below).
    $labelChars = max(1, ...array_map('mb_strlen', $labels ?: ['']));
    $first = $slides[0] ?? null;
    $isSlideshow = $count > 1;

    // Photos 2..n are created by JS after window "load" (not in the HTML).
    $config = [
        'duration' => $settings['heroSlideDuration'],
        'slides' => array_map(fn (array $slide): array => [
            'src' => $slide['image']['large']['url'],
            'srcset' => MediaPresenter::srcset($slide['image']),
            'width' => $slide['image']['large']['width'],
            'height' => $slide['image']['large']['height'],
            'alt' => $slide['image']['alt'],
        ], $slides),
    ];
@endphp
<section
    id="home"
    class="hero relative isolate flex min-h-[max(100svh,640px)] flex-col overflow-hidden on-dark bg-dark text-white"
    aria-labelledby="hero-title"
    @if ($isSlideshow)
        x-data="hero(@js($config))"
        :class="{ 'is-ready': ready, 'is-paused': paused }"
    @endif
>
    <div class="absolute inset-0 -z-10" x-ref="photos" data-parallax>
        @if ($first)
            <img
                src="{{ $first['image']['large']['url'] }}"
                srcset="{{ MediaPresenter::srcset($first['image']) }}"
                sizes="100vw"
                width="{{ $first['image']['large']['width'] }}"
                height="{{ $first['image']['large']['height'] }}"
                alt="{{ $first['image']['alt'] }}"
                fetchpriority="high"
                class="hero-photo absolute inset-0 size-full object-cover"
                data-index="0"
            >
        @endif

        {{-- Overlays (no blur, the photo stays sharp): an even overlay 18% over the whole
             photo, the left scrim (lg+, see .hero-overlay-left) and the bottom scrim above the
             statistics bar (below). Together they keep the subtext at WCAG AA on any photo. --}}
        <div class="absolute inset-0 z-10 bg-overlay/18"></div>
        <div class="hero-overlay-left absolute inset-0 z-10 hidden lg:block"></div>
        {{-- Below lg the text spans the full width: an even tint instead of the left scrim. --}}
        <div class="absolute inset-0 z-10 bg-overlay/64 lg:hidden"></div>
        <div class="hero-overlay-top absolute inset-x-0 top-0 z-10 h-44"></div>
    </div>

    {{-- Top padding = navbar height + the bottom padding, so the content is centred
         between the navbar and the statistics bar. --}}
    <x-ui.container class="relative z-10 flex flex-1 flex-col justify-center pt-[88px] pb-6 sm:pt-[104px] sm:pb-10 lg:pt-[120px] lg:pb-10 lg:short:pt-[104px] lg:short:pb-6 lg:shorter:pt-24 lg:shorter:pb-4">
        <div class="max-w-[640px]">
            {{-- 1. Badge --}}
            <div x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="0">
                <p class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/5 px-3.5 py-1.5 text-[13px] font-semibold sm:text-sm">
                    <span class="size-1.5 shrink-0 rounded-full bg-highlight" aria-hidden="true"></span>
                    <span class="shiny-text">{{ $settings['heroBadge'] }}<span class="shiny-glint" data-text="{{ $settings['heroBadge'] }}" aria-hidden="true"></span></span>
                </p>
            </div>

            {{-- 2. Headline: line 1 blur-in (balanced), line 2 rotating service label on its own line.
                 36px -> 64px with the width; smaller on short desktop screens. --}}
            <h1
                id="hero-title"
                class="@container mt-4 text-[clamp(2.25rem,1.2rem+3.3vw,4rem)] leading-[1.05] font-extrabold tracking-[-0.02em] sm:mt-6 lg:short:mt-4 lg:short:text-[clamp(2.25rem,1.2rem+2.6vw,3.25rem)] lg:shorter:mt-3 lg:shorter:text-[2.625rem]"
            >
                <span class="block text-balance" x-data="blurText" data-blur-text>{{ $settings['heroHeadline'] }}</span>
                @if ($labels)
                    <span class="sr-only">{{ implode(', ', $labels) }}</span>
                    {{-- Invisible copies of every label, stacked in one grid cell: the area takes the
                         width and height of the longest label at the current screen width, so the
                         headline keeps its line count and nothing below moves. Same word markup as
                         rotatingText, so a label that wraps has the same line height.
                         The font size shrinks only when the longest label would not fit on one line
                         (~0.56em per character, measured 0.47-0.52em), so narrow screens do not
                         reserve an empty second line. --}}
                    <span
                        class="grid text-highlight"
                        style="font-size: min(1em, calc(100cqi / ({{ $labelChars }} * 0.56)))"
                        aria-hidden="true"
                    >
                        @foreach ($labels as $label)
                            <span class="invisible col-start-1 row-start-1">@foreach (explode(' ', $label) as $w => $word){{ $w > 0 ? ' ' : '' }}<span class="rt-word">{{ $word }}</span>@endforeach</span>
                        @endforeach
                        <span
                            class="col-start-1 row-start-1"
                            @if ($isSlideshow) x-data="rotatingText(@js($labels))" @endif
                        ><span class="rt-set">{{ $labels[0] }}</span></span>
                    </span>
                @endif
            </h1>

            {{-- 3. Subtext --}}
            @if ($settings['heroSubtext'])
                {{-- data-animate="rise": visible from the first paint (LCP), only rises 24px. --}}
                <div class="mt-4 sm:mt-6 lg:short:mt-4" x-data="animatedContent" x-intersect.once="show()" data-animate="rise" data-delay="0.2">
                    <p class="max-w-[560px] text-base leading-relaxed text-white/85 sm:text-lg lg:shorter:text-base">{{ $settings['heroSubtext'] }}</p>
                </div>
            @endif

            {{-- 4. Actions --}}
            <div class="mt-6 flex items-center gap-3 sm:mt-8 sm:gap-4 lg:short:mt-6 lg:shorter:mt-5" x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="0.3">
                <x-ui.button :href="'#'.\App\Models\PageSection::CONTACT" size="lg" class="shrink-0 shadow-lg shadow-cta-dark-bg/30">
                    {{ $settings['ctaLabel'] }}
                </x-ui.button>
                <x-ui.button href="#services" variant="secondary" size="lg" icon="arrow-right" class="group min-w-0">
                    {{ $settings['texts']['hero_secondary_cta'] }}
                </x-ui.button>
            </div>

            {{-- 5. Trust row: up to 5 client logos (admin "Logo Klien"); not rendered when empty.
                 Logos are shown white (brightness-0 invert) so any upload looks uniform. On desktop
                 the row may be wider than the text column (max 880px); on narrow screens it
                 scrolls sideways without a scrollbar. --}}
            @if ($trustLogos)
                <div class="mt-6 flex items-center gap-4 sm:mt-7 lg:w-[min(880px,calc(100vw-48px))] lg:short:mt-4 lg:shorter:mt-4" x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="0.35">
                    <p class="shrink-0 text-[13px] font-medium text-white/60">{{ $settings['texts']['trust_label'] }}</p>
                    <ul class="scrollbar-none flex min-w-0 items-center gap-6 overflow-x-auto overscroll-x-contain py-1 sm:gap-8">
                        @foreach ($trustLogos as $item)
                            @php
                                $logo = $item['logo'];
                                $width = $logo['height'] > 0 ? (int) round($logo['width'] / $logo['height'] * 24) : null;
                            @endphp
                            <li class="shrink-0">
                                <picture>
                                    @if ($logo['webp'])
                                        <source srcset="{{ $logo['webp'] }}" type="image/webp">
                                    @endif
                                    <img
                                        src="{{ $logo['url'] }}"
                                        alt="{{ $item['name'] }}"
                                        @if ($width) width="{{ $width }}" @endif
                                        height="24"
                                        decoding="async"
                                        class="h-6 w-auto max-w-[140px] object-contain opacity-70 brightness-0 invert"
                                    >
                                </picture>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- 6. Slide indicators (JS only; hidden with a single slide) --}}
            @if ($isSlideshow)
                <div class="js-only mt-6 sm:mt-8 lg:short:mt-4 lg:shorter:mt-3" x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="0.4">
                    <div
                        class="grid gap-3 sm:gap-5"
                        style="grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))"
                        role="group"
                        aria-label="{{ $settings['texts']['slides_group'] }}"
                        {{-- Pause only while a mouse is over the indicators (not text/CTA, not touch). --}}
                        @pointerenter="hover = $event.pointerType === 'mouse'"
                        @pointerleave="hover = false"
                    >
                        {{-- Equal columns; a label that does not fit ends in an ellipsis. --}}
                        @foreach ($slides as $i => $slide)
                            <button
                                type="button"
                                class="group min-w-0 py-3 text-left"
                                aria-label="{{ $settings['texts']['slide_show'] }} {{ $slide['label'] }}"
                                :aria-current="index === {{ $i }} ? 'true' : null"
                                @click="hover = false; goTo({{ $i }})"
                            >
                                <span class="block h-0.5 overflow-hidden rounded-full bg-white/25">
                                    <span
                                        class="hero-progress block h-full rounded-full bg-highlight"
                                        data-progress="{{ $i }}"
                                        @animationend="onProgressEnd({{ $i }})"
                                    ></span>
                                </span>
                                <span
                                    class="mt-2.5 hidden truncate text-sm font-semibold transition-colors duration-200 sm:block"
                                    :class="index === {{ $i }} ? 'text-highlight' : 'text-white/70 group-hover:text-white/90'"
                                >{{ $slide['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-ui.container>

    {{-- Bottom gradient: from the top of the statistics bar up ~35% of the hero. --}}
    <div class="relative">
        <div class="hero-overlay-bottom pointer-events-none absolute inset-x-0 bottom-full h-[35svh]" aria-hidden="true"></div>

    @if (count($stats))
        @php
            $statCount = count($stats);
            $lastRowStart = $statCount % 2 === 0 ? $statCount - 2 : $statCount - 1;
            // When any statistic has an icon, the others keep an empty slot so numbers line up.
            $hasIcons = collect($stats)->contains(fn (array $stat): bool => $stat['icon'] !== null);
        @endphp
        {{-- Columns sit in the same container as the logo and headline: the first column
             starts at the headline edge, the last one ends at the navbar CTA edge.
             Every column has the same inner padding. --}}
        <div class="border-t border-white/10 bg-dark/55 backdrop-blur-md">
            <x-ui.container
                as="dl"
                class="grid grid-cols-2 md:grid-cols-[repeat(var(--stat-cols),minmax(0,1fr))]"
                style="--stat-cols: {{ $statCount }}"
            >
                @foreach ($stats as $i => $stat)
                    @php
                        $final = $stat['value'] !== null
                            ? \Illuminate\Support\Number::format($stat['value'], $stat['decimals'], $stat['decimals'], app()->getLocale())
                            : (string) $stat['textValue'];
                        $display = ($stat['prefix'] ?? '').$final.($stat['suffix'] ?? '');
                        // Up to 32px; a long value (e.g. a translated text) gets smaller to fit the
                        // column on one line (~0.62em per character), down to 20px, then wraps.
                        $valueChars = max(4, mb_strlen($display));
                    @endphp
                    <div @class([
                        '@container flex min-w-0 flex-col justify-center border-white/15 px-4 py-3.5 text-left sm:px-5 sm:py-5 lg:px-6 lg:py-5 lg:short:py-3.5 lg:shorter:py-3',
                        'border-r' => $i % 2 === 0 && $i !== $statCount - 1,
                        'border-b' => $i < $lastRowStart,
                        'md:border-b-0',
                        'md:border-r-0' => $i === $statCount - 1,
                        'md:border-r' => $i !== $statCount - 1,
                    ])>
                        <dt class="order-2 mt-1 text-sm leading-snug text-white/75">{{ $stat['label'] }}</dt>
                        <dd
                            class="order-1 font-extrabold tracking-tight tabular-nums"
                            style="font-size: clamp(20px, calc(100cqi / ({{ $valueChars }} * 0.62)), 32px)"
                        >
                            @if ($stat['icon'])
                                <x-ui.icon :name="$stat['icon']" class="mb-2 block size-5 text-highlight lg:shorter:mb-1.5" />
                            @elseif ($hasIcons)
                                <span class="mb-2 block size-5 lg:shorter:mb-1.5" aria-hidden="true"></span>
                            @endif
                            {{-- 40px box: one-line values line up; a wrapped value keeps tight lines. --}}
                            <span class="flex min-h-10 items-center leading-[1.15] text-balance">
                                @if ($stat['useCountUp'])
                                    <span class="sr-only">{{ $display }}</span>
                                    <span aria-hidden="true">{{ $stat['prefix'] }}<span
                                        x-data="countUp({ to: {{ $stat['value'] }}, decimals: {{ $stat['decimals'] }} })"
                                        x-intersect.once="start()"
                                    >{{ $final }}</span>{{ $stat['suffix'] }}</span>
                                @else
                                    <span>{{ $display }}</span>
                                @endif
                            </span>
                        </dd>
                    </div>
                @endforeach
            </x-ui.container>
        </div>
    @endif
    </div>
</section>
