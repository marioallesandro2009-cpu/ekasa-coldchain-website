{{-- Gallery: the service's gallery photos in a grid (the first one wider on desktop). --}}
@props(['block', 'service', 'settings', 'first' => false])
<x-ui.section :id="$block['id']" :variant="$block['variant']" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <x-ui.section-heading split :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title'] ?? $service['title']" :description="$block['description']" />
    <ul class="mt-10 grid grid-cols-2 gap-3 sm:mt-12 sm:gap-4 lg:grid-cols-3" role="list">
        @foreach ($block['images'] as $i => $image)
            <li
                @class(['relative aspect-[4/3] overflow-hidden rounded-card bg-surface', 'col-span-2 lg:row-span-2 lg:aspect-auto' => $i === 0 && count($block['images']) > 2])
                x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="{{ ($i % 3) * 0.08 }}"
            >
                <img
                    src="{{ $image['small']['url'] }}"
                    srcset="{{ \App\Support\MediaPresenter::srcset($image) }}"
                    sizes="(min-width: 1024px) 33vw, 50vw"
                    width="{{ $image['large']['width'] }}"
                    height="{{ $image['large']['height'] }}"
                    alt="{{ $image['alt'] }}"
                    loading="lazy"
                    decoding="async"
                    class="absolute inset-0 size-full object-cover"
                >
            </li>
        @endforeach
    </ul>
</x-ui.section>
