{{--
    Home page contact section (#contact): a proper B2B conversion layout —
    left column real contact channels (WhatsApp/email/phone from SiteSetting,
    address/hours from the first active Location — HQ, by admin order), right
    column a lead form. No backend endpoint exists yet, so the form is not a
    dead UI: on submit it builds a prefilled WhatsApp message (via the same
    wa.me link SiteSetting already exposes) and opens it — a real, working
    action rather than a fake "sent" state. A small capability strip closes
    the section. Nothing here is fabricated: every channel, address and hour
    is admin data; the four capability labels describe the business itself
    (cold storage, 24/7 operations), not invented numbers.
--}}
@props(['section', 'settings', 'locations', 'services'])
@php
    $hq = $locations[0] ?? null;
    $wa = $settings['whatsappUrl'];
    $needs = collect($services)->filter(fn (array $s): bool => $s['url'] !== null)->values();
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
        tone="dark"
    />

    <div class="mt-10 grid gap-10 sm:mt-12 lg:grid-cols-12 lg:gap-8">
        {{-- Left: real contact channels. --}}
        <div class="lg:col-span-5">
            <h3 class="text-sm font-bold tracking-[0.14em] text-highlight uppercase">{{ __('Contact Us') }}</h3>

            <ul class="mt-5 space-y-5" role="list">
                @if ($wa)
                    <li class="flex items-start gap-4">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-white/10 text-highlight">
                            <x-ui.icon name="chat-bubble" class="size-5" />
                        </span>
                        <div class="min-w-0 pt-1.5">
                            <a href="{{ $wa }}" target="_blank" rel="noopener" class="font-semibold text-white hover:text-highlight">WhatsApp</a>
                            <p class="mt-0.5 text-sm text-white/65">{{ __('Fastest way to reach us') }}</p>
                        </div>
                    </li>
                @endif
                @if ($settings['email'])
                    <li class="flex items-start gap-4">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-white/10 text-highlight">
                            <x-ui.icon name="email" class="size-5" />
                        </span>
                        <div class="min-w-0 pt-1.5">
                            <a href="mailto:{{ $settings['email'] }}" class="font-semibold text-white hover:text-highlight break-all">{{ $settings['email'] }}</a>
                            <p class="mt-0.5 text-sm text-white/65">{{ __('Email') }}</p>
                        </div>
                    </li>
                @endif
                @if ($settings['phone'])
                    <li class="flex items-start gap-4">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-white/10 text-highlight">
                            <x-ui.icon name="phone" class="size-5" />
                        </span>
                        <div class="min-w-0 pt-1.5">
                            <a href="tel:{{ $settings['phone'] }}" class="font-semibold text-white hover:text-highlight">{{ $settings['phone'] }}</a>
                            <p class="mt-0.5 text-sm text-white/65">{{ __('Phone') }}</p>
                        </div>
                    </li>
                @endif
                @if ($hq && $hq['address'])
                    <li class="flex items-start gap-4">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-white/10 text-highlight">
                            <x-ui.icon name="map-pin" class="size-5" />
                        </span>
                        <div class="min-w-0 pt-1.5">
                            <p class="font-semibold text-white">{{ $hq['address'] }}</p>
                            <p class="mt-0.5 text-sm text-white/65">{{ $hq['city'] }}</p>
                        </div>
                    </li>
                @endif
                @if ($hq && $hq['hours'])
                    <li class="flex items-start gap-4">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-white/10 text-highlight">
                            <x-ui.icon name="opening-hours" class="size-5" />
                        </span>
                        <div class="min-w-0 pt-1.5">
                            <p class="font-semibold text-white">{{ $hq['hours'] }}</p>
                            <p class="mt-0.5 text-sm text-white/65">{{ __('Opening hours') }}</p>
                        </div>
                    </li>
                @endif
            </ul>
        </div>

        {{-- Right: lead form. No backend yet — submits as a prefilled WhatsApp message. --}}
        <div class="lg:col-span-7">
            <div
                class="rounded-card border border-white/15 bg-white/[0.04] p-6 sm:p-8"
                x-data="{
                    name: '', company: '', contactInfo: '', need: '', message: '', sent: false,
                    send() {
                        const lines = [
                            {{ Js::from(__('New inquiry via website:')) }},
                            {{ Js::from(__('Name')) }} + ': ' + this.name,
                            {{ Js::from(__('Company')) }} + ': ' + this.company,
                            {{ Js::from(__('Email / WhatsApp')) }} + ': ' + this.contactInfo,
                            this.need ? ({{ Js::from(__('Need')) }} + ': ' + this.need) : null,
                            this.message ? ('\n' + this.message) : null,
                        ].filter(Boolean).join('\n');
                        window.open('{{ $wa }}?text=' + encodeURIComponent(lines), '_blank', 'noopener');
                        this.sent = true;
                    }
                }"
            >
                <h3 class="text-lg font-bold text-white">{{ __('Send a Request') }}</h3>

                <template x-if="!sent">
                    <form class="mt-5 space-y-4" @submit.prevent="send()">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="contact-name" class="text-sm font-medium text-white/80">{{ __('Name') }}</label>
                                <input id="contact-name" x-model="name" required type="text" class="mt-1.5 h-11 w-full rounded-full border border-white/20 bg-white/5 px-4 text-white placeholder-white/40 outline-none focus:border-highlight" placeholder="{{ __('Your name') }}">
                            </div>
                            <div>
                                <label for="contact-company" class="text-sm font-medium text-white/80">{{ __('Company') }}</label>
                                <input id="contact-company" x-model="company" type="text" class="mt-1.5 h-11 w-full rounded-full border border-white/20 bg-white/5 px-4 text-white placeholder-white/40 outline-none focus:border-highlight" placeholder="{{ __('Company name') }}">
                            </div>
                        </div>
                        <div>
                            <label for="contact-info" class="text-sm font-medium text-white/80">{{ __('Email / WhatsApp') }}</label>
                            <input id="contact-info" x-model="contactInfo" required type="text" class="mt-1.5 h-11 w-full rounded-full border border-white/20 bg-white/5 px-4 text-white placeholder-white/40 outline-none focus:border-highlight" placeholder="{{ __('How can we reach you?') }}">
                        </div>
                        @if ($needs->isNotEmpty())
                            <div>
                                <label for="contact-need" class="text-sm font-medium text-white/80">{{ __('Need') }}</label>
                                <select id="contact-need" x-model="need" class="mt-1.5 h-11 w-full rounded-full border border-white/20 bg-white/5 px-4 text-white outline-none focus:border-highlight">
                                    <option value="" class="bg-dark">{{ __('Select a service') }}</option>
                                    @foreach ($needs as $service)
                                        <option value="{{ $service['title'] }}" class="bg-dark">{{ $service['title'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <label for="contact-message" class="text-sm font-medium text-white/80">{{ __('Message (optional)') }}</label>
                            <textarea id="contact-message" x-model="message" rows="3" class="mt-1.5 w-full rounded-2xl border border-white/20 bg-white/5 px-4 py-3 text-white placeholder-white/40 outline-none focus:border-highlight" placeholder="{{ __('Tell us a bit more...') }}"></textarea>
                        </div>
                        <button type="submit" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-highlight px-6 font-semibold text-dark transition-transform hover:scale-[1.01] sm:w-auto sm:px-8">
                            {{ __('Ask about availability') }}
                        </button>
                    </form>
                </template>

                <template x-if="sent">
                    <div class="mt-5 flex items-start gap-3 rounded-2xl border border-highlight/30 bg-highlight/10 p-4">
                        <x-ui.icon name="chat-bubble" class="mt-0.5 size-5 shrink-0 text-highlight" />
                        <p class="text-sm text-white/85">{{ __('Your message is ready on WhatsApp — send it there to reach our team directly.') }}</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Capability strip: describes the operation itself, no invented numbers. --}}
    <ul class="mt-12 grid grid-cols-2 gap-px overflow-hidden rounded-card border border-white/15 bg-white/15 sm:grid-cols-4" role="list">
        @foreach ([
            ['clock', __('24/7 Monitoring')],
            ['thermometer-snowflake', __('Temperature Controlled')],
            ['stopwatch', __('Fast Response')],
            ['map-pin', __('Nationwide Coverage')],
        ] as [$icon, $label])
            <li class="flex flex-col items-center gap-2 bg-dark px-4 py-6 text-center">
                <x-ui.icon :name="$icon" class="size-5 text-highlight" />
                <span class="text-sm font-medium text-white/80">{{ $label }}</span>
            </li>
        @endforeach
    </ul>
</x-ui.section>
