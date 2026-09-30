{{--
    Blog post detail page: the only routed blog page (one-page site — the #blog
    section on the home page is the "index"). Cover photo, title, date, body,
    then a link back to that #blog section (Locales::path(...) . '#blog').
--}}
@php
    $home = \App\Support\Locales::path(\App\Support\Locales::current());
    $blogAnchor = $home.'#'.\App\Models\PageSection::BLOG;
@endphp
<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">
    <x-layout.navbar :settings="$settings" :services="$services" :featured-services="$featuredServices" :sections="$sections" solid />

    @if ($preview)
        <p class="fixed inset-x-0 bottom-0 z-40 bg-highlight px-4 py-2 text-center text-sm font-semibold text-dark" role="status">
            {{ __('Preview (draft)') }}: {{ __('This page is a draft: only admins can see it.') }}
        </p>
    @endif

    <main id="main">
        <article class="container-site pt-24 pb-16 lg:pt-32 lg:pb-24">
            <nav aria-label="{{ __('Breadcrumb') }}">
                <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-ink-muted">
                    <li><a href="{{ $home }}" class="rounded hover:text-brand">{{ __('Home') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ $blogAnchor }}" class="rounded hover:text-brand">{{ __('Blog') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="truncate font-semibold text-ink">{{ $post['title'] }}</li>
                </ol>
            </nav>

            <header class="mt-4 max-w-3xl">
                @if ($post['publishedAt'])
                    <time datetime="{{ $post['publishedAt'] }}" class="text-sm font-bold tracking-[0.12em] text-brand uppercase">
                        {{ $post['publishedAtDisplay'] }}
                    </time>
                @endif
                <h1 class="mt-2 text-[clamp(1.75rem,1.1rem+2.4vw,2.75rem)] leading-[1.1] font-extrabold tracking-[-0.02em] text-balance">{{ $post['title'] }}</h1>
                @if ($post['excerpt'])
                    <p class="mt-3 text-lg leading-relaxed text-ink-muted">{{ $post['excerpt'] }}</p>
                @endif
            </header>

            @if ($post['image'])
                <div class="mt-8 overflow-hidden rounded-card">
                    <img
                        src="{{ $post['image']['small']['url'] }}"
                        srcset="{{ \App\Support\MediaPresenter::srcset($post['image']) }}"
                        sizes="(min-width: 1024px) 900px, 100vw"
                        width="{{ $post['image']['large']['width'] }}"
                        height="{{ $post['image']['large']['height'] }}"
                        alt="{{ $post['image']['alt'] }}"
                        class="w-full object-cover"
                        fetchpriority="high"
                    >
                </div>
            @endif

            <div class="blog-content mt-10 max-w-3xl">
                {!! $post['content'] !!}
            </div>

            <div class="mt-12 max-w-3xl border-t border-ink/10 pt-6">
                <a href="{{ $blogAnchor }}" class="inline-flex items-center gap-1.5 font-semibold text-brand hover:text-brand-800">
                    <x-ui.icon name="arrow-right" class="size-4 rotate-180" />
                    {{ __('Back to blog') }}
                </a>
            </div>
        </article>
    </main>

    <x-layout.footer :settings="$settings" :sections="$sections" />
</x-layouts::app>
