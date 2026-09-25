{{-- Advantages: icon, title and text per item, fading in one after another. --}}
@props(['block', 'service', 'settings', 'first' => false])
@php($count = count($block['items']))
<x-ui.section :id="$block['id']" :variant="$block['variant']" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <x-ui.section-heading split :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title'] ?? $service['title']" :description="$block['description']" />
    <ul @class([
        'mt-10 grid gap-6 sm:mt-12 sm:grid-cols-2',
        'lg:grid-cols-3' => $count === 3 || $count > 4,
        'lg:grid-cols-4' => $count === 4,
    ]) role="list">
        @foreach ($block['items'] as $i => $item)
            <li
                @class(['rounded-card border border-ink/10 p-6', 'bg-white' => $block['variant'] === 'surface', 'bg-surface' => $block['variant'] !== 'surface'])
                x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="{{ ($i % 4) * 0.08 }}"
            >
                @if ($item['icon'])
                    <span class="grid size-11 place-items-center rounded-lg bg-brand/10 text-brand">
                        <x-ui.icon :name="$item['icon']" class="size-6" />
                    </span>
                @endif
                <h3 @class(['text-lg font-bold', 'mt-4' => $item['icon']])>{{ $item['title'] }}</h3>
                @if ($item['text'])
                    <p class="mt-2 text-ink-muted">{{ $item['text'] }}</p>
                @endif
            </li>
        @endforeach
    </ul>
</x-ui.section>
