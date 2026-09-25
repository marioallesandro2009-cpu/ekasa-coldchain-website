{{-- One service link in the mega menu. Hover/focus: surface background, solid brand icon box. --}}
@props(['service'])
<a
    href="{{ \App\Support\Links::service($service) }}"
    class="group flex items-start gap-4 rounded-[10px] p-4 transition-colors duration-150 hover:bg-surface focus-visible:bg-surface"
    @click="closeMega()"
>
    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand/10 text-brand transition-colors duration-150 group-hover:bg-brand group-hover:text-white group-focus-visible:bg-brand group-focus-visible:text-white">
        <x-ui.icon :name="$service['icon']" class="size-5" />
    </span>
    <span class="min-w-0">
        <span class="block font-semibold text-ink">{{ $service['title'] }}</span>
        @if ($service['description'])
            <span class="mt-0.5 block text-sm leading-snug text-ink-muted">{{ $service['description'] }}</span>
        @endif
    </span>
</a>
