{{-- Text + photo side by side (photo left or right from the admin; stacked on phones). --}}
@props(['block', 'service', 'settings', 'first' => false])
@php($image = $block['image'])
<x-ui.section :id="$block['id']" :variant="$block['variant']" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <div @class(['grid items-center gap-10 lg:grid-cols-2 lg:gap-16', 'lg:[&>*:first-child]:order-2' => $block['imageLeft'] && $image])>
        <div>
            <x-ui.section-heading split :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title'] ?? $service['title']" :description="$block['description']" />
            @if ($block['text'])
                <div class="mt-5 space-y-4 text-lg leading-relaxed text-ink-muted" x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="0.1">
                    @foreach (preg_split('/\n\s*\n/', $block['text']) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            @endif
            @if ($block['points'])
                <ul class="mt-6 space-y-3" role="list" x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="0.2">
                    @foreach ($block['points'] as $point)
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-brand/10 text-brand">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true" focusable="false"><path d="M5 12.5 9.5 17 19 7.5"/></svg>
                            </span>
                            <span class="font-medium">{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        @if ($image)
            <div class="relative aspect-[4/3] overflow-hidden rounded-card bg-surface" x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="0.15">
                <img
                    src="{{ $image['small']['url'] }}"
                    srcset="{{ \App\Support\MediaPresenter::srcset($image) }}"
                    sizes="(min-width: 1280px) 600px, (min-width: 1024px) 50vw, 100vw"
                    width="{{ $image['large']['width'] }}"
                    height="{{ $image['large']['height'] }}"
                    alt=""
                    loading="lazy"
                    decoding="async"
                    class="absolute inset-0 size-full object-cover"
                >
            </div>
        @endif
    </div>
</x-ui.section>
