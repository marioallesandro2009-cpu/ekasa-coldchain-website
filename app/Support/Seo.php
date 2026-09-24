<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Meta tag values for public pages (title, description, canonical, Open Graph, preload).
 */
final class Seo
{
    /**
     * Canonical site root from APP_URL (not from the request host).
     */
    public static function baseUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * @param  array{settings: array<string, mixed>, heroSlides: list<array<string, mixed>>}  $data
     * @return array{title: string, description: string, canonical: string, alternates: array<string, string>, ogLocale: string, ogAlternates: list<string>, siteName: string, image: array{url: string, width: int, height: int}|null, imageAlt: string, preload: array<string, mixed>|null, themeColor: string}
     */
    public static function home(array $data): array
    {
        $settings = $data['settings'];
        $locale = Locales::current();
        $firstSlide = $data['heroSlides'][0]['image'] ?? null;

        // Share image: dedicated OG image, else the first hero photo, else the logo.
        $image = $settings['seo']['image']
            ?? ($firstSlide['large'] ?? null)
            ?? ($settings['logoColor'] ? self::logoSize($settings['logoColor']) : null);

        return [
            'title' => (string) $settings['seo']['title'],
            'description' => Str::limit(trim(strip_tags((string) $settings['seo']['description'])), 300, ''),
            // Each language is canonical to itself; hreflang links the versions.
            'canonical' => Locales::url($locale),
            'alternates' => Locales::alternates(),
            'ogLocale' => Locales::ogLocale($locale),
            'ogAlternates' => array_values(array_map(Locales::ogLocale(...), array_diff(Locales::all(), [$locale]))),
            'siteName' => (string) $settings['companyName'],
            'image' => $image,
            'imageAlt' => (string) ($firstSlide['alt'] ?? $settings['companyName']),
            'preload' => $firstSlide,
            // Browser UI color on phones: the navy brand color.
            'themeColor' => (string) $settings['colors']['navy'],
        ];
    }

    /**
     * @param  array{url: string, width: int, height: int}  $logo
     * @return array{url: string, width: int, height: int}
     */
    private static function logoSize(array $logo): array
    {
        return ['url' => $logo['url'], 'width' => $logo['width'], 'height' => $logo['height']];
    }
}
