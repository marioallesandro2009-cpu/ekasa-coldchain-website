{{--
    Compact spec card for one truck (fleet showcase): name, type badge, up to four
    spec rows (bars only where a relative comparison makes sense: payload, volume),
    notes and the WhatsApp/contact CTA. Reused floating (desktop, over the photo) and
    in normal flow (mobile, below the photo) by the caller via wrapping classes.
--}}
@props(['item'])
<div {{ $attributes->class(['rounded-xl border border-ink/10 bg-white/90 p-5 shadow-lg backdrop-blur-sm']) }}>
    <span class="inline-flex rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-semibold text-brand">{{ $item['typeLabel'] }}</span>
    <h3 class="mt-2 text-lg font-extrabold tracking-tight text-ink">{{ $item['name'] }}</h3>
    @if ($item['specs'])
        {{-- Each row is a <dl> child div holding exactly one dt + one dd (the only
             nesting the definition-list content model allows); the bar lives inside
             the dd itself rather than as a sibling div, since dl > div > div(dt, dd)
             or dl > div > (dt, dd, div) both fail the dt/dd-only-children rule. --}}
        <dl class="mt-3 space-y-2.5">
            @foreach ($item['specs'] as $spec)
                <div class="flex items-start justify-between gap-3 text-sm">
                    <dt class="pt-0.5 text-ink-muted">{{ $spec['label'] }}</dt>
                    <dd class="flex flex-1 flex-col items-end font-bold tabular-nums text-ink">
                        <span>{{ $spec['value'] }}</span>
                        @if ($spec['bar'] !== null)
                            <div class="mt-1 h-1.5 w-full max-w-24 overflow-hidden rounded-full bg-ink/10" aria-hidden="true">
                                <div class="spec-bar h-full rounded-full bg-brand" style="width: {{ $spec['bar'] }}%"></div>
                            </div>
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    @endif
    @if ($item['notes'])
        <p class="mt-3 text-xs text-ink-muted">{{ $item['notes'] }}</p>
    @endif
    <x-ui.button
        :href="$item['ask']['href']"
        tone="light"
        icon="arrow-right"
        class="group mt-4 w-full justify-center"
        :target="$item['ask']['external'] ? '_blank' : null"
        :rel="$item['ask']['external'] ? 'noopener' : null"
    >{{ $item['ask']['label'] }}<span class="sr-only">: {{ $item['name'] }}</span></x-ui.button>
</div>
