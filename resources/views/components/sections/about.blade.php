{{--
    Home page about section (#about): a split layout (framed photo + company
    story + count-up stats) followed by an asymmetric bento grid of "why choose
    us" value cards (same 1-large + up-to-3-small structure as #services'
    bento, for visual consistency across the site). Every word, number, icon
    and photo comes from the admin (AboutContent singleton — story/photo;
    AboutStat — the count-up row; AboutValue — the pillar cards) via
    FrontendData::build()'s "about" key. Nothing hardcoded: if the admin has
    not filled a piece in yet, that piece simply does not render.
--}}
@props(['section', 'about'])
@php
    $dark = $section['background'] === 'dark';
    $content = $about['content'];
    $stats = $about['stats'];
    $values = $about['values'];
    $hasStory = $content['story'] || $content['photo'];

    // Bento: the first value (admin order) as the large card, the rest (at
    // most 3) stacked beside it — same shape as x-sections.services' bento.
    $leadValue = $values[0] ?? null;
    $sideValues = array_slice($values, 1, 3);
    $leadClass = match (count($sideValues)) {
        0 => 'lg:col-span-12',
        1 => 'lg:col-span-7',
        2 => 'lg:col-span-7 lg:row-span-2',
        default => 'lg:col-span-7 lg:row-span-3',
    };
    // Explicit literal classes only (Tailwind can't see interpolated ones).
    $statsColsClass = match (count($stats)) {
        1 => 'sm:grid-cols-1',
        2 => 'sm:grid-cols-2',
        3 => 'sm:grid-cols-3',
        default => 'sm:grid-cols-4',
    };
@endphp
<x-ui.section
    :id="$section['id']"
    :variant="$section['background']"
    class="relative isolate flex min-h-screen flex-col justify-center overflow-hidden"
    aria-labelledby="{{ $section['id'] }}-title"
    x-ignore
    data-alpine-defer
>
    <div
        class="pointer-events-none absolute -top-24 -right-24 -z-10 size-[28rem] rounded-full bg-brand/10 blur-3xl"
        aria-hidden="true"
    ></div>

    <x-ui.section-heading
        split
        :id="$section['id'].'-title'"
        :eyebrow="$section['eyebrow']"
        :title="$section['title']"
        :description="$section['description']"
        :tone="$dark ? 'dark' : 'light'"
    />

    @if ($hasStory)
        <div class="mt-10 grid gap-10 sm:mt-12 lg:grid-cols-2 lg:items-center lg:gap-16">
            @if ($content['photo'])
                <div
                    class="relative mx-auto w-full max-w-md lg:mx-0 lg:max-w-none"
                    x-data="animatedContent"
                    x-intersect.once="show()"
                    data-animate="rise"
                >
                    <div
                        class="absolute -top-4 -left-4 -z-10 hidden aspect-[4/5] w-full rounded-card bg-brand/15 sm:block lg:-top-6 lg:-left-6"
                        aria-hidden="true"
                    ></div>
                    <div class="aspect-[4/5] overflow-hidden rounded-card shadow-xl">
                        <img
                            src="{{ $content['photo']['small']['url'] }}"
                            srcset="{{ \App\Support\MediaPresenter::srcset($content['photo']) }}"
                            sizes="(min-width: 1024px) 40vw, (min-width: 640px) 448px, 100vw"
                            width="{{ $content['photo']['large']['width'] }}"
                            height="{{ $content['photo']['large']['height'] }}"
                            alt="{{ $content['photo']['alt'] }}"
                            loading="lazy"
                            decoding="async"
                            class="size-full object-cover"
                        >
                    </div>
                </div>
            @endif

            <div class="{{ $content['photo'] ? '' : 'lg:col-span-2 lg:max-w-3xl' }}">
                @if ($content['story'])
                    <div
                        class="blog-content {{ $dark ? 'text-white/85' : '' }}"
                        x-data="animatedContent"
                        x-intersect.once="show()"
                        data-animate
                        data-delay="0.1"
                    >
                        {!! $content['story'] !!}
                    </div>
                @endif

                @if ($stats)
                    <dl
                        class="mt-8 grid grid-cols-2 gap-x-6 gap-y-6 border-t {{ $dark ? 'border-white/15' : 'border-ink/10' }} pt-6 {{ $statsColsClass }}"
                        x-data="animatedList"
                        data-animated-list
                    >
                        @foreach ($stats as $stat)
                            @php
                                $final = $stat['value'] !== null
                                    ? \Illuminate\Support\Number::format($stat['value'], $stat['decimals'], $stat['decimals'], app()->getLocale())
                                    : (string) $stat['textValue'];
                            @endphp
                            <div>
                                @if ($stat['icon'])
                                    <x-ui.icon :name="$stat['icon']" class="mb-2 size-5 {{ $dark ? 'text-highlight' : 'text-brand' }}" />
                                @endif
                                <dd class="text-2xl font-extrabold tabular-nums sm:text-3xl">
                                    @if ($stat['useCountUp'])
                                        <span aria-hidden="true">{{ $stat['prefix'] }}<span
                                            x-data="countUp({ to: {{ $stat['value'] }}, decimals: {{ $stat['decimals'] }} })"
                                            x-intersect.once="start()"
                                        >{{ $final }}</span>{{ $stat['suffix'] }}</span>
                                        <span class="sr-only">{{ $stat['prefix'] }}{{ $final }}{{ $stat['suffix'] }}</span>
                                    @else
                                        {{ $stat['prefix'] }}{{ $final }}{{ $stat['suffix'] }}
                                    @endif
                                </dd>
                                <dt class="mt-1 text-sm {{ $dark ? 'text-white/70' : 'text-ink-muted' }}">{{ $stat['label'] }}</dt>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>
    @endif

    @if ($leadValue)
        <ul
            class="{{ $hasStory ? 'mt-14 sm:mt-16' : 'mt-10 sm:mt-12' }} grid gap-4 sm:gap-5 lg:grid-cols-12"
            role="list"
            x-data="animatedList"
            data-animated-list
        >
            <li class="{{ $leadClass }} relative isolate flex min-h-[16rem] flex-col overflow-hidden rounded-card border {{ $dark ? 'border-white/15 bg-white/5' : 'border-ink/10 bg-white' }} p-8 sm:p-10">
                @if ($leadValue['icon'])
                    <x-ui.icon
                        :name="$leadValue['icon']"
                        class="pointer-events-none absolute -right-6 -bottom-6 -z-10 size-40 {{ $dark ? 'text-white/[0.06]' : 'text-brand/[0.07]' }}"
                        aria-hidden="true"
                    />
                    <span class="grid size-16 place-items-center rounded-full {{ $dark ? 'bg-white/10 text-highlight' : 'bg-brand/10 text-brand' }}">
                        <x-ui.icon :name="$leadValue['icon']" class="size-8" />
                    </span>
                @endif
                <h3 class="mt-6 text-2xl leading-tight font-extrabold {{ $dark ? 'text-white' : 'text-ink' }}">{{ $leadValue['title'] }}</h3>
                @if ($leadValue['description'])
                    <p class="mt-3 max-w-md text-base leading-relaxed {{ $dark ? 'text-white/75' : 'text-ink-muted' }}">{{ $leadValue['description'] }}</p>
                @endif
            </li>

            @foreach ($sideValues as $value)
                <li class="lg:col-span-5 flex items-start gap-4 rounded-card border {{ $dark ? 'border-white/15 bg-white/5' : 'border-ink/10 bg-white' }} p-6">
                    @if ($value['icon'])
                        <span class="grid size-11 shrink-0 place-items-center rounded-full {{ $dark ? 'bg-white/10 text-highlight' : 'bg-brand/10 text-brand' }}">
                            <x-ui.icon :name="$value['icon']" class="size-5" />
                        </span>
                    @endif
                    <div class="min-w-0">
                        <h3 class="text-base font-bold {{ $dark ? 'text-white' : 'text-ink' }}">{{ $value['title'] }}</h3>
                        @if ($value['description'])
                            <p class="mt-1.5 text-sm leading-relaxed {{ $dark ? 'text-white/70' : 'text-ink-muted' }}">{{ $value['description'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @unless ($hasStory || $leadValue)
        <p class="mt-8 {{ $dark ? 'text-white/70' : 'text-ink-muted' }}">Belum ada konten. Isi lewat admin: Cerita Perusahaan, Statistik Tentang Kami, atau Nilai/Keunggulan.</p>
    @endunless
</x-ui.section>
