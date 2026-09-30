<?php

namespace App\Support;

use App\Models\AboutContent;
use App\Models\AboutStat;
use App\Models\AboutValue;
use App\Models\HeroSlide;
use App\Models\HeroStat;
use App\Models\Location;
use App\Models\PageSection;
use App\Models\Post;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\TrustLogo;
use Illuminate\Support\Facades\Cache;

/**
 * Cached data for the public site, one entry per language. Flushed (all
 * languages) whenever admin data changes.
 */
final class FrontendData
{
    /**
     * @return array{settings: array<string, mixed>, services: list<array<string, mixed>>, locations: list<array<string, mixed>>, featuredServices: list<array<string, mixed>>, heroSlides: list<array<string, mixed>>, heroStats: list<array<string, mixed>>, sections: list<array<string, mixed>>, recentPosts: list<array<string, mixed>>, about: array{content: array<string, mixed>, stats: list<array<string, mixed>>, values: list<array<string, mixed>>}, trustLogos: list<array<string, mixed>>, clientLogos: list<array<string, mixed>>}
     */
    public static function all(?string $locale = null): array
    {
        $locale ??= Locales::current();

        return Cache::rememberForever(self::key($locale), fn (): array => self::build($locale));
    }

    /**
     * The data in one language (texts use that language, falling back to English).
     *
     * @return array{settings: array<string, mixed>, services: list<array<string, mixed>>, locations: list<array<string, mixed>>, featuredServices: list<array<string, mixed>>, heroSlides: list<array<string, mixed>>, heroStats: list<array<string, mixed>>, sections: list<array<string, mixed>>, recentPosts: list<array<string, mixed>>, about: array{content: array<string, mixed>, stats: list<array<string, mixed>>, values: list<array<string, mixed>>}, trustLogos: list<array<string, mixed>>, clientLogos: list<array<string, mixed>>}
     */
    private static function build(string $locale): array
    {
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            return [
                'settings' => SiteSetting::current()->toFrontend(),
                'services' => array_values(Service::query()->active()->ordered()->with(['media', 'cardFleetVehicle.media'])->get()->map->toFrontend()->all()),
                'locations' => array_values(Location::query()->active()->ordered()->with('media')->get()->map->toFrontend()->all()),
                // Single source for "featured, capped": the home teaser and the navbar
                // mega menu both consume this exact list instead of each filtering
                // $services on their own, so they can never disagree. /services itself
                // stays unbounded (uses the full 'services' list above).
                'featuredServices' => array_values(Service::query()->active()->featured()->ordered()->with(['media', 'cardFleetVehicle.media'])->take(Service::MAX_FEATURED)->get()->map->toFrontend()->all()),
                'heroSlides' => array_values(HeroSlide::query()->active()->ordered()->with('media')->get()
                    ->map->toFrontend()->filter()->all()),
                'heroStats' => array_values(HeroStat::query()->active()->ordered()->get()->map->toFrontend()->all()),
                'sections' => array_values(PageSection::query()->active()->ordered()->get()->map->toFrontend()->all()),
                // The home page's #blog section teaser — same "single capped source"
                // pattern as featuredServices above, so nothing else has to re-filter.
                'recentPosts' => array_values(Post::query()->active()->published()->ordered()->with('media')->take(Post::MAX_RECENT)->get()->map->toFrontend()->all()),
                // The #about section: story + photo (singleton), stats and value
                // cards — each a single capped/ordered source, same pattern as above.
                'about' => [
                    'content' => AboutContent::current()->toFrontend(),
                    'stats' => array_values(AboutStat::query()->active()->ordered()->get()->map->toFrontend()->all()),
                    'values' => array_values(AboutValue::query()->active()->ordered()->get()->map->toFrontend()->all()),
                ],
                // Single source, capped two different ways: the hero's own small
                // trust row (trustLogos, MAX_VISIBLE) and the full #clients section
                // logo loop (clientLogos, every active one) — same underlying query,
                // never two different orderings of "who our clients are".
                'trustLogos' => array_slice($clientLogos = array_values(TrustLogo::query()->active()->ordered()->with('media')->get()
                    ->map->toFrontend()->filter()->all()), 0, TrustLogo::MAX_VISIBLE),
                'clientLogos' => $clientLogos,
            ];
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function settings(?string $locale = null): array
    {
        return self::all($locale)['settings'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function services(?string $locale = null): array
    {
        return self::all($locale)['services'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function locations(?string $locale = null): array
    {
        return self::all($locale)['locations'];
    }

    public static function flush(): void
    {
        foreach (Locales::all() as $locale) {
            Cache::forget(self::key($locale));
        }

        // Other cached pages (service detail pages) carry this version in their key.
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 0) + 1);
    }

    private const VERSION_KEY = 'frontend.version';

    /**
     * Cache other public page data (e.g. a service page) until admin data changes.
     *
     * @template T
     *
     * @param  \Closure(): T  $build
     * @return T
     */
    public static function remember(string $key, \Closure $build): mixed
    {
        $version = (int) Cache::get(self::VERSION_KEY, 0);

        return Cache::rememberForever(config('site.cache_key').".{$key}.v{$version}", $build);
    }

    private static function key(?string $locale = null): string
    {
        return config('site.cache_key').'.'.($locale ?? Locales::current());
    }
}
