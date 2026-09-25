{{-- Minimal footer (full footer is a later stage). --}}
@props(['settings'])
<footer class="on-dark bg-dark py-8 text-sm text-white/75">
    <x-ui.container class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p>&copy; {{ now()->year }} {{ $settings['legalName'] }}. {{ $settings['texts']['footer_copyright'] }}</p>
        @if ($settings['email'] || $settings['phone'])
            <p class="flex flex-wrap gap-x-5 gap-y-1">
                @if ($settings['email'])
                    <a href="mailto:{{ $settings['email'] }}" class="hover:text-white">{{ $settings['email'] }}</a>
                @endif
                @if ($settings['phone'])
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $settings['phone']) }}" class="hover:text-white">{{ $settings['phone'] }}</a>
                @endif
            </p>
        @endif
    </x-ui.container>
</footer>
