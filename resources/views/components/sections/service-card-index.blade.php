{{--
    One card on the /services index page: same bento proportions and photo-with-
    dark-gradient text treatment as the home page card (sections.service-card),
    but with the three "vehicle configurator" motifs from Bagian 1: a giant
    transparent ordinal number overlapping the photo, a slow always-on Ken Burns
    zoom, and a notched corner (clip-path) instead of a rounded one.

    compact (Bagian 2, home page teaser): number + title + photo only — no
    description, key points or "Learn more" text (the card is still one big
    link). Same component on both pages keeps the visual system (number,
    Ken Burns, notch) identical between the teaser and the full index.
--}}
@props(['service', 'number', 'size' => 'small', 'texts', 'delay' => 0, 'compact' => false])
@php
    $large = $size === 'large';
    $image = $service['image'];
    $href = $service['url'] ?? '#'.\App\Models\PageSection::CONTACT;
    $ordinal = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
@endphp
<li {{ $attributes->class(['min-w-0']) }} x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="{{ $delay }}">
    <article
        x-data="spotlightCard"
        @class([
            'spotlight-card card-notch group relative isolate flex h-full min-h-56 flex-col justify-end overflow-hidden bg-dark text-white',
            'has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-2 has-[a:focus-visible]:outline-brand',
            'aspect-[4/5] sm:aspect-[4/3] lg:aspect-auto lg:min-h-full' => $large,
            'aspect-[16/9] lg:aspect-auto' => ! $large,
        ])
    >
        @if ($image)
            <img
                src="{{ $image['small']['url'] }}"
                srcset="{{ \App\Support\MediaPresenter::srcset($image) }}"
                sizes="{{ $large ? '(min-width: 1280px) 720px, (min-width: 1024px) 58vw, 100vw' : '(min-width: 1280px) 510px, (min-width: 1024px) 42vw, 100vw' }}"
                width="{{ $image['large']['width'] }}"
                height="{{ $image['large']['height'] }}"
                alt=""
                loading="lazy"
                decoding="async"
                style="object-position: {{ $image['focus'] }}"
                class="ken-burns absolute inset-0 -z-20 size-full object-cover"
            >
        @endif

        {{-- Giant ordinal, transparent, overlapping the photo: 01 -> 04 as a
             visual echo of the connector line running through the grid. Pale
             white reads fine on a dark/uniform photo; on a bright/busy one
             (card_photo_light, an admin toggle — not auto-detected brightness)
             it needs dark, more opaque ink instead to still register at all. --}}
        <span
            @class([
                'pointer-events-none absolute top-3 right-4 -z-10 font-extrabold tabular-nums select-none sm:top-4 sm:right-6',
                'text-ink/60' => $service['photoLight'],
                'text-white/10' => ! $service['photoLight'],
            ])
            style="font-size: clamp(4.5rem, 3rem + 6vw, 9.5rem); line-height: 0.85"
            aria-hidden="true"
        >{{ $ordinal }}</span>

        {{-- Text shade: dark from the bottom up, enough for white text (AA). --}}
        <div @class([
            'absolute inset-x-0 bottom-0 -z-10 bg-linear-to-t from-overlay via-overlay/85 to-overlay/0',
            'h-full sm:h-3/4' => $large,
            'h-3/4' => ! $large,
        ]) aria-hidden="true"></div>

        <div @class(['p-6 sm:p-8' => $large, 'p-4 sm:p-5' => ! $large])>
            <div class="flex items-center gap-3">
                <span @class(['grid shrink-0 place-items-center rounded-lg bg-white/15 text-highlight backdrop-blur-sm', 'size-11' => $large, 'size-9' => ! $large])>
                    <x-ui.icon :name="$service['icon']" @class(['size-6' => $large, 'size-5' => ! $large]) />
                </span>
                <h2 @class(['leading-snug font-bold', 'text-2xl sm:text-3xl' => $large, 'text-lg' => ! $large])>
                    <a href="{{ $href }}" class="focus-visible:outline-none after:absolute after:inset-0 after:content-['']">{{ $service['title'] }}</a>
                </h2>
            </div>
            @if ($service['description'] && ! $compact)
                <p @class(['text-white/85', 'mt-3 text-lg' => $large, 'mt-1.5 truncate text-sm' => ! $large])>{{ $service['description'] }}</p>
            @endif
            @if ($large && $service['keyPoints'] && ! $compact)
                <ul class="mt-4 space-y-2 text-[15px]" role="list">
                    @foreach ($service['keyPoints'] as $point)
                        <li class="flex items-start gap-2.5">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 size-4.5 shrink-0 text-highlight" aria-hidden="true" focusable="false"><path d="M5 12.5 9.5 17 19 7.5"/></svg>
                            <span>{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($large && ! $compact)
                <span class="mt-6 inline-flex items-center gap-1.5 font-semibold text-highlight" aria-hidden="true">
                    {{ $texts['service_card_link'] }}
                    <x-ui.icon name="arrow-right" class="size-4 transition-transform duration-200 group-hover:translate-x-0.5" />
                </span>
            @endif
        </div>
    </article>
</li>
