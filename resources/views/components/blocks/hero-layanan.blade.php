{{--
    Service hero: full-width photo with the dark overlay on the left (like the home
    hero), breadcrumb, eyebrow, title (splitText), intro, key points and buttons.
--}}
@props(['block', 'service', 'settings', 'first' => false])
@php
    use App\Support\Locales;

    $image = $block['image'];
    $home = Locales::path(Locales::current());
@endphp
<section id="{{ $block['id'] }}" class="on-dark relative isolate flex min-h-[max(100svh,640px)] flex-col justify-end overflow-hidden bg-dark text-white" aria-labelledby="{{ $block['id'] }}-title">
    @if ($image)
        <img
            src="{{ $image['large']['url'] }}"
            srcset="{{ \App\Support\MediaPresenter::srcset($image) }}"
            sizes="100vw"
            width="{{ $image['large']['width'] }}"
            height="{{ $image['large']['height'] }}"
            alt=""
            @if ($first) fetchpriority="high" @else loading="lazy" @endif
            decoding="async"
            class="absolute inset-0 -z-20 size-full object-cover"
        >
    @endif
    {{-- Left and bottom shade for the text, top shade for the navbar. --}}
    <div class="absolute inset-0 -z-10 bg-linear-to-r from-overlay/90 via-overlay/60 to-overlay/10" aria-hidden="true"></div>
    <div class="absolute inset-x-0 bottom-0 -z-10 h-2/5 bg-linear-to-t from-overlay/70 to-overlay/0" aria-hidden="true"></div>
    <div class="hero-overlay-top absolute inset-x-0 top-0 -z-10 h-44" aria-hidden="true"></div>

    <x-ui.container class="pt-32 pb-14 sm:pb-16 lg:pt-40 lg:pb-20">
        <nav aria-label="{{ __('Breadcrumb') }}">
            <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-white/75">
                <li><a href="{{ $home }}" class="rounded hover:text-white">{{ __('Home') }}</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ \App\Models\Service::indexPath() }}" class="rounded hover:text-white">{{ __('Services') }}</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="text-white">{{ $service['title'] }}</li>
            </ol>
        </nav>

        <div class="mt-6 max-w-3xl">
            @if ($block['eyebrow'])
                <p class="text-sm font-bold tracking-[0.14em] text-highlight uppercase">{{ $block['eyebrow'] }}</p>
            @endif
            <h1
                id="{{ $block['id'] }}-title"
                x-data="splitText"
                data-split-text="h1"
                @class(['text-[clamp(2.25rem,1.3rem+3vw,3.75rem)] leading-[1.05] font-extrabold tracking-[-0.02em] text-balance', 'mt-3' => $block['eyebrow']])
            >{{ $block['title'] }}</h1>
            @if ($block['description'])
                <p class="mt-5 max-w-2xl text-lg leading-relaxed text-white/85" x-data="animatedContent" x-intersect.once="show()" data-animate="rise" data-delay="0.2">{{ $block['description'] }}</p>
            @endif
            @if ($block['keyPoints'])
                <ul class="mt-6 flex flex-wrap gap-2" role="list" x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="0.3">
                    @foreach ($block['keyPoints'] as $point)
                        <li class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-sm font-medium">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0 text-highlight" aria-hidden="true" focusable="false"><path d="M5 12.5 9.5 17 19 7.5"/></svg>
                            {{ $point }}
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-8 flex flex-wrap items-center gap-3 sm:gap-4" x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="0.4">
                <x-ui.button :href="$block['cta']['href']" size="lg" :target="$block['cta']['external'] ? '_blank' : null" :rel="$block['cta']['external'] ? 'noopener' : null">{{ $block['cta']['label'] }}</x-ui.button>
                @if ($block['secondaryLabel'] && ($block['nextId'] ?? null))
                    <x-ui.button :href="'#'.$block['nextId']" variant="secondary" size="lg" icon="arrow-right" class="group">{{ $block['secondaryLabel'] }}</x-ui.button>
                @endif
            </div>
        </div>
    </x-ui.container>
</section>
