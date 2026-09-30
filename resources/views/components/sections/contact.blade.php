{{--
    Home page contact section (#contact): a B2B conversion layout in two columns.
    Left: heading, intro, real contact channels (email/phone from SiteSetting,
    address/hours from the first active Location — HQ, by admin order) and, when a
    number is set, a separate WhatsApp button for a quick chat without the form.
    Right: the lead form. It is a real HTML form (POST + CSRF + redirect with a flash
    message), so it works without JavaScript; with Alpine (contactForm) the same
    form is sent with fetch and answered inline. The server decides what is valid.
    Spam: a hidden honeypot field (a bot that fills it gets a fake "sent" and nothing
    is stored) and a rate limit per IP (see ContactController / AppServiceProvider).
    Nothing here is fabricated: every channel, address and hour is admin data; the
    four capability labels describe the business itself, not invented numbers.
--}}
@props(['section', 'settings', 'locations', 'services'])
@php
    $hq = $locations[0] ?? null;
    $wa = $settings['whatsappUrl'];
    $texts = $settings['texts'];
    $errors = $errors->getBag('contact');
    $success = session('contact_status');
    $blocked = session('contact_error');
    $input = 'mt-1.5 h-11 w-full rounded-full border bg-white/5 px-4 text-white placeholder-white/65 outline-none focus:border-highlight focus:ring-2 focus:ring-highlight/60';
    // Invalid fields get a red border (server flash or client errors), the message under the field.
    $border = fn (string $field): string => $errors->has($field) ? 'border-red-300!' : 'border-white/50';
    $waMessage = __('Hi, I would like to ask about your cold chain services.');
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
        {{-- Left: real contact channels + separate WhatsApp button. --}}
        <div class="lg:col-span-5" x-data="animatedContent" x-intersect.once="show()" data-animate>
            <h3 class="text-sm font-bold tracking-[0.14em] text-highlight uppercase">{{ __('Contact Us') }}</h3>

            <ul class="mt-5 space-y-5" role="list">
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

            @if ($wa)
                <div class="mt-8 rounded-card border border-white/15 bg-white/[0.04] p-5">
                    <p class="text-sm font-semibold text-white">{{ __('Prefer a quick answer?') }}</p>
                    <p class="mt-1 text-sm text-white/70">{{ __('Fastest way to reach us') }}</p>
                    <a
                        href="{{ $wa }}?text={{ rawurlencode($waMessage) }}"
                        target="_blank"
                        rel="noopener"
                        class="mt-4 inline-flex h-12 items-center justify-center gap-2 rounded-full border border-white/40 px-6 font-semibold text-white transition-colors hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-highlight"
                    >
                        <x-ui.icon name="message-circle" class="size-5" />
                        {{ $texts['whatsapp_button'] }}
                    </a>
                </div>
            @endif
        </div>

        {{-- Right: the lead form. --}}
        <div class="lg:col-span-7" x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="0.15">
            <div
                class="rounded-card border border-white/15 bg-white/[0.04] p-6 sm:p-8"
                x-data="contactForm({ error: @js($texts['contact_error']), errors: @js(collect($errors->messages())->map(fn ($messages) => $messages[0])->all()) })"
            >
                <h3 class="text-lg font-bold text-white">{{ __('Send a Request') }}</h3>
                <p class="mt-1 text-sm text-white/70">{{ __('Fill in the form and we will reply by email.') }}</p>

                {{-- Server-rendered result (plain POST). Alpine removes it when the next fetch starts. --}}
                @if ($success || $blocked)
                    <div x-ref="flash" role="status" tabindex="-1" class="mt-5 flex items-start gap-3 rounded-2xl border p-4 {{ $success ? 'border-highlight/30 bg-highlight/10' : 'border-red-300/50 bg-red-400/10' }}">
                        <x-ui.icon name="chat-bubble" class="mt-0.5 size-5 shrink-0 {{ $success ? 'text-highlight' : 'text-red-300' }}" />
                        <p class="text-sm text-white/90">{{ $success ?: $blocked }}</p>
                    </div>
                @endif

                {{-- Client-side result (fetch). --}}
                <div x-cloak x-show="message" x-ref="live" role="status" aria-live="polite" tabindex="-1" class="mt-5 flex items-start gap-3 rounded-2xl border p-4" :class="ok ? 'border-highlight/30 bg-highlight/10' : 'border-red-300/50 bg-red-400/10'">
                    <p class="text-sm text-white/90" x-text="message"></p>
                </div>

                <form
                    class="mt-5 space-y-4"
                    method="POST"
                    action="{{ \App\Support\Links::contactAction() }}"
                    @submit.prevent="submit($event)"
                    x-ref="form"
                >
                    @csrf
                    {{-- Honeypot: humans never see or fill this. --}}
                    <div aria-hidden="true" style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;">
                        <label>Website <input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="contact-name" class="text-sm font-medium text-white/80">{{ __('Name') }} <span aria-hidden="true">*</span></label>
                            <input id="contact-name" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" type="text" placeholder="{{ __('Your name') }}" class="{{ $input }} {{ $border('name') }}" :class="errors.name && 'border-red-300!'" :aria-invalid="errors.name ? 'true' : null" aria-describedby="contact-name-error" @if ($errors->has('name')) aria-invalid="true" @endif>
                            <p id="contact-name-error" class="mt-1 text-sm text-red-300" x-text="errors.name">{{ $errors->first('name') }}</p>
                        </div>
                        <div>
                            <label for="contact-email" class="text-sm font-medium text-white/80">{{ __('Email') }} <span aria-hidden="true">*</span></label>
                            <input id="contact-email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email" type="email" placeholder="{{ __('Your email') }}" class="{{ $input }} {{ $border('email') }}" :class="errors.email && 'border-red-300!'" :aria-invalid="errors.email ? 'true' : null" aria-describedby="contact-email-error" @if ($errors->has('email')) aria-invalid="true" @endif>
                            <p id="contact-email-error" class="mt-1 text-sm text-red-300" x-text="errors.email">{{ $errors->first('email') }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="contact-phone" class="text-sm font-medium text-white/80">{{ __('Phone (optional)') }}</label>
                            <input id="contact-phone" name="phone" value="{{ old('phone') }}" maxlength="40" autocomplete="tel" type="tel" placeholder="+62 812 3456 7890" class="{{ $input }} {{ $border('phone') }}" :class="errors.phone && 'border-red-300!'" :aria-invalid="errors.phone ? 'true' : null" aria-describedby="contact-phone-error" @if ($errors->has('phone')) aria-invalid="true" @endif>
                            <p id="contact-phone-error" class="mt-1 text-sm text-red-300" x-text="errors.phone">{{ $errors->first('phone') }}</p>
                        </div>
                        <div>
                            <label for="contact-company" class="text-sm font-medium text-white/80">{{ __('Company (optional)') }}</label>
                            <input id="contact-company" name="company" value="{{ old('company') }}" maxlength="150" autocomplete="organization" type="text" placeholder="{{ __('Company name') }}" class="{{ $input }} {{ $border('company') }}">
                        </div>
                    </div>

                    @if (count($services) > 0)
                        <div>
                            <label for="contact-service" class="text-sm font-medium text-white/80">{{ __('Service of interest (optional)') }}</label>
                            <select id="contact-service" name="service_id" class="{{ $input }} border-white/50">
                                <option value="" class="bg-dark">{{ __('Select a service') }}</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service['id'] }}" class="bg-dark" @selected((string) old('service_id') === (string) $service['id'])>{{ $service['title'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="contact-message" class="text-sm font-medium text-white/80">{{ __('Message') }} <span aria-hidden="true">*</span></label>
                        <textarea id="contact-message" name="message" required minlength="10" maxlength="3000" rows="4" placeholder="{{ __('How can we help?') }}" class="mt-1.5 w-full rounded-2xl border bg-white/5 px-4 py-3 text-white placeholder-white/65 outline-none focus:border-highlight focus:ring-2 focus:ring-highlight/60 {{ $border('message') }}" :class="errors.message && 'border-red-300!'" :aria-invalid="errors.message ? 'true' : null" aria-describedby="contact-message-error" @if ($errors->has('message')) aria-invalid="true" @endif>{{ old('message') }}</textarea>
                        <p id="contact-message-error" class="mt-1 text-sm text-red-300" x-text="errors.message">{{ $errors->first('message') }}</p>
                    </div>

                    @if (\App\Support\Turnstile::enabled())
                        {{-- Cloudflare Turnstile (TURNSTILE_ENABLED): needs JavaScript; its token is posted as
                             cf-turnstile-response, checked in ContactController. --}}
                        <div class="cf-turnstile" data-sitekey="{{ \App\Support\Turnstile::siteKey() }}" data-theme="dark" data-language="{{ app()->getLocale() }}"></div>
                        <noscript><p class="text-sm text-white/75">{{ __('Please enable JavaScript to send the form, or use WhatsApp.') }}</p></noscript>
                        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer @if ($nonce = \Illuminate\Support\Facades\Vite::cspNonce()) nonce="{{ $nonce }}" @endif></script>
                    @endif

                    <button
                        type="submit"
                        class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-highlight px-6 font-semibold text-dark transition-transform hover:scale-[1.01] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white disabled:cursor-wait disabled:opacity-70 sm:w-auto sm:px-8"
                        :disabled="loading"
                        :aria-busy="loading.toString()"
                    >
                        <span x-show="!loading">{{ __('Send Message') }}</span>
                        <span x-cloak x-show="loading">{{ __('Sending...') }}</span>
                    </button>
                </form>
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
