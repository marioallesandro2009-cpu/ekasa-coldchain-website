{{-- Service detail page: the blocks from the admin (Builder), in their order.
     Below the first screen, the blocks' Alpine components start near the viewport. --}}
<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">
    <x-layout.navbar :settings="$settings" :services="$services" :sections="$sections" />

    @if ($preview)
        <p class="fixed inset-x-0 bottom-0 z-40 bg-highlight px-4 py-2 text-center text-sm font-semibold text-dark" role="status">
            {{ __('Preview (draft)') }}: {{ __('This page is a draft: only admins can see it.') }}
        </p>
    @endif

    <main id="main">
        @foreach ($page['blocks'] as $index => $block)
            <x-dynamic-component
                :component="'blocks.'.str_replace('_', '-', $block['type'])"
                :block="$block"
                :service="$page['service']"
                :settings="$settings"
                :first="$index === 0"
            />
        @endforeach
    </main>

    <x-layout.footer :settings="$settings" />
</x-layouts::app>
