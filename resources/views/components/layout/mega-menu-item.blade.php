{{-- One service link in the mega menu. Hover/focus: surface background, solid ice icon box. --}}
@props(['service'])
<a
    href="#{{ $service['anchor'] }}"
    class="group flex items-start gap-4 rounded-[10px] p-4 transition-colors duration-150 hover:bg-surface focus-visible:bg-surface"
    @click="closeMega()"
>
    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-ice/10 text-navy transition-colors duration-150 group-hover:bg-ice group-hover:text-white group-focus-visible:bg-ice group-focus-visible:text-white">
        <x-ui.icon :name="$service['icon']" class="size-5" />
    </span>
    <span class="min-w-0">
        <span class="block font-semibold text-ink">{{ $service['title'] }}</span>
        @if ($service['description'])
            <span class="mt-0.5 block text-sm leading-snug text-ink-muted">{{ $service['description'] }}</span>
        @endif
    </span>
</a>
