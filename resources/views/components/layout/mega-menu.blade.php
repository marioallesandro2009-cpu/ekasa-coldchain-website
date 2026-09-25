{{--
    Services mega menu panel. Plain links in the HTML; shown on hover/focus
    without JS (CSS) and controlled by the navbar component with JS.
--}}
@props(['services', 'megaMenu', 'contactId'])
@php
    // Main services (is_featured) in a 2x2 grid; the others in full-width rows below a divider.
    $featured = array_values(array_filter($services, fn (array $service): bool => $service['featured']));
    $others = array_values(array_filter($services, fn (array $service): bool => ! $service['featured']));
@endphp
<div
    id="mega-menu-services"
    {{ $attributes->class('mega-panel absolute inset-x-0 top-full pt-2') }}
>
    <x-ui.container>
        <div class="mega-card grid grid-cols-[minmax(0,1fr)_320px] gap-6 rounded-2xl bg-white p-5 text-ink ring-1 ring-ink/5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="flex flex-col">
                @if ($featured)
                    <ul class="grid grid-cols-2 content-start gap-1">
                        @foreach ($featured as $service)
                            <li><x-layout.mega-menu-item :service="$service" /></li>
                        @endforeach
                    </ul>
                @endif

                @if ($others)
                    <ul @class(['grid gap-1', 'mt-3 border-t border-ink/10 pt-3' => $featured])>
                        @foreach ($others as $service)
                            <li><x-layout.mega-menu-item :service="$service" /></li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <a
                href="{{ \App\Support\Links::section($contactId) }}"
                class="group relative block aspect-[4/3] overflow-hidden rounded-card bg-dark"
                @click="closeMega()"
            >
                @if ($image = $megaMenu['image'])
                    <img
                        src="{{ $image['small']['url'] }}"
                        srcset="{{ \App\Support\MediaPresenter::srcset($image) }}"
                        sizes="(min-width: 1280px) 360px, 320px"
                        width="{{ $image['large']['width'] }}"
                        height="{{ $image['large']['height'] }}"
                        alt=""
                        loading="lazy"
                        decoding="async"
                        class="absolute inset-0 size-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                    >
                @endif
                <span class="absolute inset-0 bg-linear-to-t from-overlay/95 via-overlay/45 to-overlay/0" aria-hidden="true"></span>
                <span class="absolute inset-x-0 bottom-0 p-5 text-white">
                    <span class="block text-lg leading-snug font-bold">{{ $megaMenu['title'] }}</span>
                    <span class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-highlight">
                        {{ $megaMenu['linkLabel'] }}
                        <x-ui.icon name="arrow-right" class="size-4 transition-transform duration-200 group-hover:translate-x-0.5" />
                    </span>
                </span>
            </a>
        </div>
    </x-ui.container>
</div>
