{{--
    End-to-end flow: steps in a row joined by a line that fills from left to right once
    visible (stacked on phones, the line fills top to bottom).
--}}
@props(['block', 'service', 'settings', 'first' => false])
@php($count = count($block['steps']))
<x-ui.section :id="$block['id']" :variant="$block['variant']" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <x-ui.section-heading split :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title'] ?? $service['title']" :description="$block['description']" />

    <div class="relative mt-10 sm:mt-12" style="--steps: {{ $count }}" x-data="animatedContent" x-intersect.once="show()" data-draw>
        {{-- Connector: behind the icons, from the first icon to the last. --}}
        <div class="absolute top-7 bottom-7 left-7 w-1 -translate-x-1/2 rounded-full bg-ink/10 lg:top-7 lg:right-[calc(50%/var(--steps))] lg:bottom-auto lg:left-[calc(50%/var(--steps))] lg:h-1 lg:w-auto lg:translate-x-0 lg:-translate-y-1/2" aria-hidden="true">
            <div class="flow-fill size-full rounded-full bg-brand-soft"></div>
        </div>
        <ol class="relative grid gap-8 lg:grid-cols-[repeat(var(--steps),minmax(0,1fr))] lg:gap-6" role="list">
            @foreach ($block['steps'] as $i => $step)
                <li class="flex gap-5 lg:flex-col lg:items-center lg:text-center">
                    <span class="relative grid size-14 shrink-0 place-items-center rounded-full border-4 border-white bg-brand text-white shadow-sm" aria-hidden="true">
                        @if ($step['icon'])
                            <x-ui.icon :name="$step['icon']" class="size-6" />
                        @else
                            <span class="font-extrabold">{{ $i + 1 }}</span>
                        @endif
                    </span>
                    <div class="pt-2 lg:pt-4">
                        <h3 class="font-bold"><span class="sr-only">{{ $i + 1 }}. </span>{{ $step['title'] }}</h3>
                        @if ($step['text'])
                            <p class="mt-1 text-sm text-ink-muted lg:mx-auto lg:max-w-[22ch]">{{ $step['text'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</x-ui.section>
