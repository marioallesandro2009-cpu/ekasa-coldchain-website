{{-- How it works: numbered steps (in a row on desktop, stacked on phones). --}}
@props(['block', 'service', 'settings', 'first' => false])
@php($count = count($block['steps']))
<x-ui.section :id="$block['id']" :variant="$block['variant']" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <x-ui.section-heading split :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title'] ?? $service['title']" :description="$block['description']" />
    <ol @class([
        'mt-10 grid gap-6 sm:mt-12 sm:grid-cols-2',
        'lg:grid-cols-3' => $count === 3,
        'lg:grid-cols-4' => $count >= 4,
    ])>
        @foreach ($block['steps'] as $i => $step)
            <li class="relative" x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="{{ ($i % 4) * 0.1 }}">
                <span class="grid size-11 place-items-center rounded-full bg-brand text-lg font-extrabold text-white tabular-nums" aria-hidden="true">{{ $i + 1 }}</span>
                <h3 class="mt-4 text-lg font-bold"><span class="sr-only">{{ $i + 1 }}. </span>{{ $step['title'] }}</h3>
                @if ($step['text'])
                    <p class="mt-2 text-ink-muted">{{ $step['text'] }}</p>
                @endif
            </li>
        @endforeach
    </ol>
</x-ui.section>
