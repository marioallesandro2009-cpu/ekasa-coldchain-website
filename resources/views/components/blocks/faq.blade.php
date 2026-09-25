{{-- FAQ: native details/summary (works without JS), items appear with animatedList. --}}
@props(['block', 'service', 'settings', 'first' => false])
<x-ui.section :id="$block['id']" :variant="$block['variant']" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <div class="grid gap-10 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-16">
        <x-ui.section-heading split :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title'] ?? 'FAQ'" :description="$block['description']" />
        <div x-data="animatedList" data-animated-list class="space-y-3">
            @foreach ($block['items'] as $item)
                <details @class(['group rounded-card border border-ink/10', 'bg-white' => $block['variant'] === 'surface', 'bg-surface' => $block['variant'] !== 'surface'])>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 rounded-card p-5 font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                        {{ $item['question'] }}
                        <x-ui.icon name="chevron-down" class="size-5 shrink-0 text-ink-muted transition-transform duration-200 group-open:rotate-180" />
                    </summary>
                    <p class="px-5 pb-5 leading-relaxed text-ink-muted">{{ $item['answer'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</x-ui.section>
