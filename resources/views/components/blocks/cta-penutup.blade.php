{{-- Closing call to action on a dark band: title, text and one button (#contact or WhatsApp). --}}
@props(['block', 'service', 'settings', 'first' => false])
<x-ui.section :id="$block['id']" variant="dark" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <div class="flex flex-col items-start gap-8 lg:flex-row lg:items-end lg:justify-between">
        <x-ui.section-heading split tone="dark" :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title']" :description="$block['description']" />
        <div class="shrink-0" x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="0.2">
            <x-ui.button
                :href="$block['cta']['href']"
                size="lg"
                icon="arrow-right"
                class="group"
                :target="$block['cta']['external'] ? '_blank' : null"
                :rel="$block['cta']['external'] ? 'noopener' : null"
            >{{ $block['cta']['label'] }}</x-ui.button>
        </div>
    </div>
</x-ui.section>
