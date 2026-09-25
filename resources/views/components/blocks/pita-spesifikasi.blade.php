{{-- Spec band: numbers on a dark band; countUp for positive numbers (final value in the HTML). --}}
@props(['block', 'service', 'settings', 'first' => false])
<x-ui.section :id="$block['id']" variant="dark" class="py-12! lg:py-16!" :aria-label="$block['title'] ?? $service['title']" x-ignore data-alpine-defer>
    @if ($block['title'])
        <x-ui.section-heading split :id="$block['id'].'-title'" tone="dark" :eyebrow="$block['eyebrow']" :title="$block['title']" :description="$block['description']" class="mb-10" />
    @endif
    <dl @class([
        'grid grid-cols-2 gap-x-6 gap-y-8',
        'lg:grid-cols-4' => count($block['items']) === 4,
        'lg:grid-cols-3' => count($block['items']) === 3,
        'lg:grid-cols-5' => count($block['items']) === 5,
        'lg:grid-cols-6' => count($block['items']) >= 6,
    ])>
        @foreach ($block['items'] as $i => $item)
            @php($display = $item['prefix'].$item['formatted'].$item['suffix'])
            <div class="flex flex-col border-l border-white/15 pl-5" x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="{{ $i * 0.08 }}">
                <dt class="order-2 mt-1 text-sm text-white/75">{{ $item['label'] }}</dt>
                <dd class="order-1 text-3xl font-extrabold tracking-tight tabular-nums sm:text-4xl">
                    @if ($item['countUp'])
                        <span class="sr-only">{{ $display }}</span>
                        <span aria-hidden="true">{{ $item['prefix'] }}<span x-data="countUp({ to: {{ $item['value'] }}, decimals: {{ $item['decimals'] }} })" x-intersect.once="start()">{{ $item['formatted'] }}</span>{{ $item['suffix'] }}</span>
                    @else
                        {{ $display }}
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>
</x-ui.section>
