<?php

namespace App\Support;

use App\Models\HeroSlide;
use App\Models\HeroStat;
use App\Models\PageSection;
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
     * @return array{settings: array<string, mixed>, services: list<array<string, mixed>>, heroSlides: list<array<string, mixed>>, heroStats: list<array<string, mixed>>, sections: list<array<string, mixed>>, trustLogos: list<array<string, mixed>>}
     */
    public static function all(?string $locale = null): array
    {
        $locale ??= Locales::current();

        return Cache::rememberForever(self::key($locale), fn (): array => self::build($locale));
    }

    /**
     * The data in one language (texts use that language, falling back to English).
     *
     * @return array{settings: array<string, mixed>, services: list<array<string, mixed>>, heroSlides: list<array<string, mixed>>, heroStats: list<array<string, mixed>>, sections: list<array<string, mixed>>, trustLogos: list<array<string, mixed>>}
     */
    private static function build(string $locale): array
    {
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            return [
                'settings' => SiteSetting::current()->toFrontend(),
                'services' => array_values(Service::query()->active()->ordered()->with(['media', 'cardFleetVehicle.media'])->get()->map->toFrontend()->all()),
                'heroSlides' => array_values(HeroSlide::query()->active()->ordered()->with('media')->get()
                    ->map->toFrontend()->filter()->all()),
                'heroStats' => array_values(HeroStat::query()->active()->ordered()->get()->map->toFrontend()->all()),
                'sections' => array_values(PageSection::query()->active()->ordered()->get()->map->toFrontend()->all()),
                'trustLogos' => array_slice(array_values(TrustLogo::query()->active()->ordered()->with('media')->get()
                    ->map->toFrontend()->filter()->all()), 0, TrustLogo::MAX_VISIBLE),
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

    public static function flush(): void
    {
        foreach (Locales::all() as $locale) {
            Cache::forget(self::key($locale));
        }
    }

    private static function key(?string $locale = null): string
    {
        return config('site.cache_key').'.'.($locale ?? Locales::current());
    }
}
