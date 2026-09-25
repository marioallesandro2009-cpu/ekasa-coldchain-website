{{-- Industries served: icon, name and short description (data: Data Layanan > Industri). --}}
@props(['block', 'service', 'settings', 'first' => false])
<x-ui.section :id="$block['id']" :variant="$block['variant']" aria-labelledby="{{ $block['id'] }}-title" x-ignore data-alpine-defer>
    <x-ui.section-heading split :id="$block['id'].'-title'" :eyebrow="$block['eyebrow']" :title="$block['title'] ?? $service['title']" :description="$block['description']" />
    <ul class="mt-10 grid gap-4 sm:mt-12 sm:grid-cols-2 lg:grid-cols-3" role="list">
        @foreach ($block['items'] as $i => $item)
            <li
                @class(['flex items-start gap-4 rounded-card border border-ink/10 p-5', 'bg-white' => $block['variant'] === 'surface', 'bg-surface' => $block['variant'] !== 'surface'])
                x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="{{ ($i % 3) * 0.08 }}"
            >
                @if ($item['icon'])
                    <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-brand/10 text-brand">
                        <x-ui.icon :name="$item['icon']" class="size-6" />
                    </span>
                @endif
                <div>
                    <h3 class="font-bold">{{ $item['name'] }}</h3>
                    @if ($item['description'])
                        <p class="mt-1 text-sm text-ink-muted">{{ $item['description'] }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
</x-ui.section>
