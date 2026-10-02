{{--
    Site footer: brand column (logo, description, WhatsApp/phone/email),
    quick links (the same "show in nav" home sections the navbar uses, so
    the two link lists can never disagree), and a bottom bar (copyright +
    social links). Every optional piece (description, phone, email,
    WhatsApp, social links) only renders when the admin has actually filled
    it in — same "no admin content, no empty row" rule as the home sections.
--}}
@props(['settings', 'sections' => []])
@php
    $navItems = array_values(array_filter($sections, fn (array $section): bool => $section['showInNav']));
    $waMessage = __('Hi, I would like to ask about your cold chain services.');
    $waHref = $settings['whatsappUrl'] ? $settings['whatsappUrl'].'?text='.rawurlencode($waMessage) : null;

    $socialLabel = function (string $url): string {
        return match (true) {
            str_contains($url, 'instagram.com') => 'Instagram',
            str_contains($url, 'linkedin.com') => 'LinkedIn',
            default => __('Website'),
        };
    };
@endphp
<footer class="on-dark bg-dark text-white/75">
    <x-ui.container class="grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)] lg:gap-8 lg:py-16">
        <div class="sm:col-span-2 lg:col-span-1">
            <x-layout.logo :logo="$settings['logoColor'] ?? $settings['logoWhite']" :name="$settings['companyName']" :height="56" text-outline outlined />
            @if ($settings['companyDescription'])
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/60">{{ $settings['companyDescription'] }}</p>
            @endif
        </div>

        @if ($navItems)
            <div>
                <p class="text-xs font-bold tracking-[0.14em] text-white/40 uppercase">{{ __('Quick Links') }}</p>
                <ul class="mt-4 space-y-2.5 text-sm" role="list">
                    @foreach ($navItems as $item)
                        <li><a href="{{ \App\Support\Links::section($item['id']) }}" class="text-white/70 transition-colors hover:text-white">{{ $item['navLabel'] }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($waHref || $settings['phone'] || $settings['email'])
            <div>
                <p class="text-xs font-bold tracking-[0.14em] text-white/40 uppercase">{{ __('Contact') }}</p>
                <ul class="mt-4 space-y-2.5 text-sm" role="list">
                    @if ($waHref)
                        <li><a href="{{ $waHref }}" target="_blank" rel="noopener" class="text-white/70 transition-colors hover:text-white">{{ $settings['texts']['whatsapp_button'] }}</a></li>
                    @endif
                    @if ($settings['phone'])
                        <li><a href="tel:{{ preg_replace('/[^\d+]/', '', $settings['phone']) }}" class="text-white/70 transition-colors hover:text-white">{{ $settings['phone'] }}</a></li>
                    @endif
                    @if ($settings['email'])
                        <li><a href="mailto:{{ $settings['email'] }}" class="text-white/70 transition-colors hover:text-white">{{ $settings['email'] }}</a></li>
                    @endif
                </ul>
            </div>
        @endif
    </x-ui.container>

    <div class="border-t border-white/10">
        <x-ui.container class="flex flex-col gap-3 py-6 text-sm sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} {{ $settings['legalName'] }}. {{ $settings['texts']['footer_copyright'] }}</p>
            @if ($settings['sameAs'])
                <p class="flex flex-wrap gap-x-5 gap-y-1">
                    @foreach ($settings['sameAs'] as $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="hover:text-white">{{ $socialLabel($url) }}</a>
                    @endforeach
                </p>
            @endif
        </x-ui.container>
    </div>
</footer>
