{{--
    Site navbar. All links are real <a href> elements. Without JS the header is
    solid dark, the mega menu opens on hover/focus and the mobile menu via :target.
--}}
@props(['settings', 'services', 'sections'])
@php
    // Navbar items: active sections marked "show in navbar" (admin "Section Beranda").
    $items = array_values(array_filter($sections, fn (array $section): bool => $section['showInNav']));
    $contactId = \App\Models\PageSection::CONTACT;
    $texts = $settings['texts'];
    $hasMega = count($services) > 0;
@endphp
<div x-data="navbar" @keydown.escape.window="closeAll()">
    <header
        class="site-header on-dark fixed inset-x-0 top-0 z-50 text-white"
        :class="{ 'is-solid': scrolled || megaOpen, 'is-scrolled': scrolled }"
    >
        <a
            href="#main"
            class="sr-only rounded-full bg-white px-4 py-2 font-semibold text-brand focus:not-sr-only focus:absolute focus:top-3 focus:left-4 focus:z-10"
        >{{ $texts['skip_link'] }}</a>

        <x-ui.container
            class="nav-bar grid h-16 grid-cols-[1fr_auto] items-center gap-6 lg:h-20 lg:grid-cols-[1fr_auto_1fr]"
            x-bind:class="{ 'is-compact': scrolled }"
        >
            <x-layout.logo
                :logo="$settings['logoWhite']"
                :name="$settings['companyName']"
                :height="48"
                class="justify-self-start"
            />

            <nav class="hidden lg:block" aria-label="{{ $texts['nav_main'] }}">
                <ul class="flex items-center gap-1 xl:gap-2">
                    @foreach ($items as $item)
                        @if (! empty($item['mega']) && $hasMega)
                            <li
                                class="mega"
                                x-ref="mega"
                                @pointerenter="megaEnter($event)"
                                @pointerleave="megaLeave($event)"
                                @focusout="megaFocusOut($event)"
                                @click.outside="closeMega()"
                            >
                                <a
                                    href="{{ \App\Support\Links::section($item['id']) }}"
                                    class="nav-link relative inline-flex h-10 items-center gap-1 rounded-md px-3 text-[15px] font-medium text-white/85 transition-colors duration-200 hover:text-white"
                                    :class="{ 'is-active': active === '{{ $item['id'] }}' }"
                                    data-spy="{{ $item['id'] }}"
                                    x-ref="megaTrigger"
                                    x-bind:role="'button'"
                                    x-bind:aria-expanded="megaOpen.toString()"
                                    x-bind:aria-controls="'mega-menu-services'"
                                    aria-haspopup="true"
                                    @click.prevent="toggleMega()"
                                    @keydown.space.prevent="toggleMega()"
                                    @keydown.arrow-down.prevent="openMega(true)"
                                >
                                    {{ $item['navLabel'] }}
                                    <x-ui.icon
                                        name="chevron-down"
                                        class="size-4 opacity-80 transition-transform duration-200"
                                        x-bind:class="{ 'rotate-180': megaOpen }"
                                    />
                                </a>

                                <x-layout.mega-menu
                                    :services="$services"
                                    :mega-menu="$settings['megaMenu']"
                                    :contact-id="$contactId"
                                    x-bind:class="{ 'is-open': megaOpen }"
                                />
                            </li>
                        @else
                            <li>
                                <a
                                    href="{{ \App\Support\Links::section($item['id']) }}"
                                    class="nav-link relative inline-flex h-10 items-center rounded-md px-3 text-[15px] font-medium text-white/85 transition-colors duration-200 hover:text-white"
                                    :class="{ 'is-active': active === '{{ $item['id'] }}' }"
                                    :aria-current="active === '{{ $item['id'] }}' ? 'location' : null"
                                    data-spy="{{ $item['id'] }}"
                                >{{ $item['navLabel'] }}</a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </nav>

            <div class="flex items-center justify-self-end gap-5">
                <x-layout.language-switch :label="$texts['language']" class="hidden lg:flex" />

                <div class="hidden lg:block">
                    <x-ui.button :href="\App\Support\Links::section($contactId)" class="nav-cta">
                        {{ $settings['ctaLabel'] }}
                    </x-ui.button>
                </div>

                <a
                    href="#mobile-menu"
                    class="-mr-2 grid size-12 place-items-center rounded-full transition-colors hover:bg-white/10 lg:hidden"
                    aria-label="{{ $texts['menu_open'] }}"
                    x-ref="hamburger"
                    x-bind:role="'button'"
                    x-bind:aria-controls="'mobile-menu'"
                    x-bind:aria-expanded="mobileOpen.toString()"
                    @click.prevent="openMobile()"
                >
                    <x-ui.icon name="menu" class="size-6" />
                </a>
            </div>
        </x-ui.container>
    </header>

    {{-- Dims the page under the navbar while the mega menu is open; a click closes it.
         Outside <header> (z-40 < header z-50), so it sits below the navbar and the panel. --}}
    @if ($hasMega)
        <div
            class="mega-backdrop fixed inset-0 z-40 hidden lg:block"
            :class="{ 'is-open': megaOpen }"
            aria-hidden="true"
            @click="closeMega()"
        ></div>
    @endif

    {{-- Outside <header>: its backdrop-filter would otherwise trap the fixed panel. --}}
    <x-layout.mobile-menu
        :settings="$settings"
        :services="$services"
        :items="$items"
        :contact-id="$contactId"
    />
</div>
