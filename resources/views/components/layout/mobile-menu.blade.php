{{--
    Full-screen mobile menu. Without JS it opens with :target (#mobile-menu);
    with JS the navbar component opens it with a staggered entrance.
    The Services accordion is a native <details>, so it works without JS too.
--}}
@props(['settings', 'services', 'items', 'contactId'])
<div
    id="mobile-menu"
    class="mobile-menu fixed inset-0 z-[70] h-dvh lg:hidden"
    :class="{ 'is-open': mobileOpen, 'is-closing': mobileClosing }"
    role="dialog"
    aria-modal="true"
    aria-label="{{ $settings['texts']['menu_dialog'] }}"
    x-ref="mobileMenu"
    @keydown="trapFocus($event)"
>
    <div class="mm-layer absolute inset-0 bg-brand-soft" aria-hidden="true"></div>
    <div class="mm-layer mm-layer-2 absolute inset-0 bg-brand" aria-hidden="true"></div>

    <div class="mm-layer mm-panel absolute inset-0 on-dark flex flex-col bg-dark text-white">
        <x-ui.container class="flex h-16 shrink-0 items-center justify-between">
            <x-layout.logo :logo="$settings['logoWhite']" :name="$settings['companyName']" :height="40" :max-width="180" @click="closeMobile(false)" />
            <a
                href="#"
                class="-mr-2 grid size-12 place-items-center rounded-full text-white transition-colors hover:bg-white/10"
                aria-label="{{ $settings['texts']['menu_close'] }}"
                x-ref="mobileClose"
                @click.prevent="closeMobile(true)"
            >
                <x-ui.icon name="x" class="size-6" />
            </a>
        </x-ui.container>

        <nav class="flex-1 overflow-y-auto overscroll-contain" aria-label="{{ $settings['texts']['nav_mobile'] }}">
            <x-ui.container as="ul" class="divide-y divide-white/10 py-2">
                @foreach ($items as $i => $item)
                    <li class="mm-item" style="--i: {{ $i }}">
                        @if (! empty($item['mega']) && count($services))
                            <details class="mm-details group">
                                <summary class="flex min-h-14 cursor-pointer items-center justify-between gap-4 py-3 text-xl font-semibold text-white/90">
                                    <span class="flex items-center gap-3">
                                        <span class="h-5 w-0.5 rounded-full bg-highlight" x-show="active === '{{ $item['id'] }}'" x-cloak aria-hidden="true"></span>
                                        {{ $item['navLabel'] }}
                                    </span>
                                    <x-ui.icon name="chevron-down" class="mm-chevron size-5 text-white/70 transition-transform duration-300 motion-reduce:transition-none" />
                                </summary>
                                <ul class="pb-3">
                                    @foreach ($services as $service)
                                        <li>
                                            <a
                                                href="{{ \App\Support\Links::service($service) }}"
                                                class="flex min-h-14 items-center gap-4 rounded-card px-2 py-2.5 transition-colors active:bg-white/10"
                                                @click="closeMobile(false)"
                                            >
                                                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-white/10 text-highlight">
                                                    <x-ui.icon :name="$service['icon']" class="size-5" />
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block font-semibold">{{ $service['title'] }}</span>
                                                    @if ($service['description'])
                                                        <span class="block text-sm text-white/70">{{ $service['description'] }}</span>
                                                    @endif
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @else
                            <a
                                href="{{ \App\Support\Links::section($item['id']) }}"
                                class="flex min-h-14 items-center gap-3 py-3 text-xl font-semibold text-white/90"
                                :aria-current="active === '{{ $item['id'] }}' ? 'location' : null"
                                @click="closeMobile(false)"
                            >
                                <span class="h-5 w-0.5 rounded-full bg-highlight" x-show="active === '{{ $item['id'] }}'" x-cloak aria-hidden="true"></span>
                                {{ $item['navLabel'] }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </x-ui.container>
        </nav>

        <x-ui.container
            class="mm-item shrink-0 space-y-3 border-t border-white/10 pt-5 pb-[max(20px,env(safe-area-inset-bottom))]"
            style="--i: {{ count($items) }}"
        >
            <x-ui.button :href="\App\Support\Links::section($contactId)" size="lg" class="w-full" @click="closeMobile(false)">
                {{ $settings['ctaLabel'] }}
            </x-ui.button>
            @if ($settings['whatsappUrl'])
                <a
                    href="{{ $settings['whatsappUrl'] }}"
                    target="_blank"
                    rel="noopener"
                    class="flex h-12 w-full items-center justify-center gap-2 rounded-full border border-white/40 px-6 text-base font-semibold text-white transition-colors hover:bg-white/10"
                >
                    <x-ui.icon name="message-circle" class="size-5" />
                    {{ $settings['texts']['whatsapp_button'] }}
                </a>
            @endif
            <x-layout.language-switch :label="$settings['texts']['language']" size="lg" class="justify-center pt-1" />
        </x-ui.container>
    </div>
</div>
