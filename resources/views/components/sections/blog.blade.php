{{--
    Home page blog teaser (#blog): heading (splitText) from "Section Beranda", the
    latest posts (FrontendData::build()'s "recentPosts", capped at Post::MAX_RECENT
    — same single-capped-source pattern as featuredServices) as a simple card grid.
    One-page/parallax site: there is no blog index page, so — unlike #services —
    there is no "View all" button here; each card links straight to that post's own
    detail page (Post::detailPath()), the only routed blog page.
--}}
@props(['section', 'posts'])
@php
    $dark = $section['background'] === 'dark';
@endphp
<x-ui.section
    :id="$section['id']"
    :variant="$section['background']"
    class="flex min-h-screen flex-col justify-center"
    aria-labelledby="{{ $section['id'] }}-title"
    x-ignore
    data-alpine-defer
>
    <x-ui.section-heading
        split
        :id="$section['id'].'-title'"
        :eyebrow="$section['eyebrow']"
        :title="$section['title']"
        :description="$section['description']"
        :tone="$dark ? 'dark' : 'light'"
    />

    @if ($posts)
        <ul class="mt-8 grid gap-6 sm:mt-10 sm:grid-cols-2 lg:grid-cols-3" role="list">
            @foreach ($posts as $i => $post)
                <li
                    x-data="animatedContent({ delay: {{ 0.1 + $i * 0.08 }} })"
                    x-ignore
                    data-alpine-defer
                >
                    <a
                        href="{{ $post['url'] }}"
                        class="group flex h-full flex-col overflow-hidden rounded-card border border-ink/10 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-brand/30 hover:shadow-lg focus-visible:-translate-y-0.5 focus-visible:border-brand/30 focus-visible:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                    >
                        <div class="aspect-[16/9] overflow-hidden bg-surface">
                            @if ($post['image'])
                                <img
                                    src="{{ $post['image']['small']['url'] }}"
                                    srcset="{{ \App\Support\MediaPresenter::srcset($post['image']) }}"
                                    sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                                    width="{{ $post['image']['large']['width'] }}"
                                    height="{{ $post['image']['large']['height'] }}"
                                    alt=""
                                    loading="lazy"
                                    decoding="async"
                                    class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
                                >
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col gap-2 p-5 sm:p-6">
                            @if ($post['publishedAt'])
                                <time datetime="{{ $post['publishedAt'] }}" class="text-xs font-bold tracking-[0.12em] text-brand uppercase">
                                    {{ $post['publishedAtDisplay'] }}
                                </time>
                            @endif
                            <h3 class="text-lg leading-snug font-bold text-ink">{{ $post['title'] }}</h3>
                            @if ($post['excerpt'])
                                <p class="line-clamp-3 text-sm text-ink-muted">{{ $post['excerpt'] }}</p>
                            @endif
                            <span class="mt-auto inline-flex items-center gap-1.5 pt-2 text-sm font-semibold text-brand" aria-hidden="true">
                                {{ __('Learn more') }}
                                <x-ui.icon name="arrow-right" class="size-4 transition-transform duration-200 group-hover:translate-x-0.5" />
                            </span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-8 text-ink-muted {{ $dark ? 'text-white/70' : '' }}">{{ __('No posts yet.') }}</p>
    @endif
</x-ui.section>
