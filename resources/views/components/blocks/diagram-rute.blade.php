{{--
    Route diagram in the style of a transit map: from the hub, one line per city with a
    45° bend, stations with the travel time. The lines "draw" once the block is visible.
    On phones (and for screen readers) the same routes as a vertical line of stops.
--}}
@props(['block', 'service', 'settings', 'first' => false])
@php($diagram = $block['diagram'])
<x-ui.section :id="$block['id']" :variant="$block['variant']" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <x-ui.section-heading split :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title'] ?? $service['title']" :description="$block['description']" />

    <div class="mt-10 sm:mt-12" x-data="animatedContent" x-intersect.once="show()" data-draw>
        <svg viewBox="0 0 1000 {{ $diagram['height'] }}" class="hidden w-full md:block" aria-hidden="true" focusable="false">
            @foreach ($diagram['lines'] as $i => $line)
                <path d="{{ $line['path'] }}" pathLength="1" fill="none" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" @class(['route-line', 'stroke-brand' => $i % 2 === 0, 'stroke-brand-soft' => $i % 2 === 1]) style="--i: {{ $i }}" />
            @endforeach
            @foreach ($block['items'] as $i => $item)
                @php($y = $diagram['lines'][$i]['y'])
                <g class="route-stop" style="--i: {{ $i }}">
                    <circle cx="760" cy="{{ $y }}" r="10" stroke-width="5" @class(['fill-white', 'stroke-brand' => $i % 2 === 0, 'stroke-brand-soft' => $i % 2 === 1]) />
                    <text x="786" y="{{ $y - 3 }}" class="fill-ink text-[19px] font-bold">{{ $item['destination'] }}</text>
                    @if ($item['eta'])
                        <text x="786" y="{{ $y + 18 }}" class="fill-ink-muted text-[15px]">{{ $item['eta'] }}</text>
                    @endif
                </g>
            @endforeach
            {{-- Hub --}}
            <circle cx="120" cy="{{ $diagram['hubY'] }}" r="24" class="fill-dark" />
            <circle cx="120" cy="{{ $diagram['hubY'] }}" r="11" class="fill-highlight" />
            <text x="120" y="{{ $diagram['hubY'] + 52 }}" text-anchor="middle" class="fill-ink text-[19px] font-extrabold">{{ $block['hub'] }}</text>
        </svg>

        {{-- Phones and screen readers: the hub, then a vertical line with every stop. --}}
        <div class="md:sr-only">
            <p class="flex items-center gap-3 font-extrabold">
                <span class="grid size-8 place-items-center rounded-full bg-dark" aria-hidden="true"><span class="size-3.5 rounded-full bg-highlight"></span></span>
                {{ $block['hub'] }}
            </p>
            <ol class="relative mt-2 ml-4 space-y-5 border-l-4 border-brand/30 pt-4 pl-7" role="list">
                @foreach ($block['items'] as $i => $item)
                    <li class="route-stop relative" style="--i: {{ $i }}">
                        <span class="absolute top-1 -left-[38px] size-4 rounded-full border-4 border-brand bg-white" aria-hidden="true"></span>
                        <span class="block font-bold">{{ $item['destination'] }}</span>
                        @if ($item['eta'])
                            <span class="block text-sm text-ink-muted">{{ $item['eta'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</x-ui.section>
