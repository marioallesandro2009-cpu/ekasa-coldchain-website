{{-- Rental schemes side by side: name, description, durations and what is included. --}}
@props(['block', 'service', 'settings', 'first' => false])
@php($count = count($block['items']))
<x-ui.section :id="$block['id']" :variant="$block['variant']" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <x-ui.section-heading split :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title'] ?? $service['title']" :description="$block['description']" />
    <ul @class(['mt-10 grid gap-6 sm:mt-12 md:grid-cols-2', 'lg:grid-cols-3' => $count >= 3]) role="list">
        @foreach ($block['items'] as $i => $item)
            <li
                @class(['flex flex-col rounded-card border border-ink/10 p-6 sm:p-7', 'bg-white' => $block['variant'] === 'surface', 'bg-surface' => $block['variant'] !== 'surface'])
                x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="{{ ($i % 3) * 0.08 }}"
            >
                <h3 class="text-xl font-extrabold">{{ $item['name'] }}</h3>
                @if ($item['description'])
                    <p class="mt-2 text-ink-muted">{{ $item['description'] }}</p>
                @endif
                @if ($item['durations'])
                    <ul class="mt-4 flex flex-wrap gap-1.5" role="list" aria-label="{{ __('Rental period') }}">
                        @foreach ($item['durations'] as $duration)
                            <li class="rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-semibold text-brand">{{ $duration }}</li>
                        @endforeach
                    </ul>
                @endif
                @if ($item['includes'])
                    <p class="mt-6 text-xs font-bold tracking-[0.12em] text-ink-muted uppercase">{{ __('Includes') }}</p>
                    <ul class="mt-3 space-y-2.5" role="list">
                        @foreach ($item['includes'] as $include)
                            <li class="flex items-start gap-2.5 text-[15px]">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 size-4.5 shrink-0 text-brand" aria-hidden="true" focusable="false"><path d="M5 12.5 9.5 17 19 7.5"/></svg>
                                <span>{{ $include }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</x-ui.section>
