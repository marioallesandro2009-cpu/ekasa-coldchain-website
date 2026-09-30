<?php

namespace App\Support;

use App\Models\PageSection;
use Illuminate\Support\Str;

/**
 * @phpstan-type SeoMeta array{title: string, description: string, canonical: string, alternates: array<string, string>, ogLocale: string, ogAlternates: list<string>, siteName: string, image: array<string, mixed>|null, imageAlt: string, preload: array<string, mixed>|null, themeColor: string}
 */
final class Seo
{
    public static function baseUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return SeoMeta
     */
    public static function home(array $data): array
    {
        $settings = $data['settings'];
        $locale = Locales::current();
        $firstSlide = $data['heroSlides'][0]['image'] ?? null;

        $image = $settings['seo']['image']
            ?? ($firstSlide['large'] ?? null)
            ?? ($settings['logoColor'] ? self::logoSize($settings['logoColor']) : null);

        return [
            'title' => (string) $settings['seo']['title'],
            'description' => Str::limit(trim(strip_tags((string) $settings['seo']['description'])), 300, ''),
            'canonical' => Locales::url($locale),
            'alternates' => Locales::alternates(),
            'ogLocale' => Locales::ogLocale($locale),
            'ogAlternates' => array_values(array_map(Locales::ogLocale(...), array_diff(Locales::all(), [$locale]))),
            'siteName' => (string) $settings['companyName'],
            'image' => $image,
            'imageAlt' => (string) ($firstSlide['alt'] ?? $settings['companyName']),
            'preload' => $firstSlide,
            'themeColor' => (string) $settings['colors']['brand'],
        ];
    }

    /**
     * @param  array{settings: array<string, mixed>, sections: list<array<string, mixed>>}  $data
     * @param  array<string, string>  $paths
     * @return SeoMeta
     */
    public static function servicesIndex(array $data, array $paths): array
    {
        $settings = $data['settings'];
        $locale = Locales::current();
        $section = collect($data['sections'])->firstWhere('id', PageSection::SERVICES);
        $alternates = array_map(fn (string $path): string => self::baseUrl().$path, $paths);
        $title = (string) ($section['navLabel'] ?? __('Services'));
        $description = $section['description'] ?? $settings['seo']['description'];
        $image = $settings['seo']['image'] ?? ($settings['logoColor'] ? self::logoSize($settings['logoColor']) : null);

        return [
            'title' => $title.' | '.$settings['companyName'],
            'description' => Str::limit(trim(strip_tags((string) $description)), 300, ''),
            'canonical' => $alternates[$locale],
            'alternates' => $alternates,
            'ogLocale' => Locales::ogLocale($locale),
            'ogAlternates' => array_values(array_map(Locales::ogLocale(...), array_diff(Locales::all(), [$locale]))),
            'siteName' => (string) $settings['companyName'],
            'image' => $image,
            'imageAlt' => (string) $settings['companyName'],
            'preload' => null,
            'themeColor' => (string) $settings['colors']['brand'],
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @param  array<string, mixed>  $settings
     * @return SeoMeta
     */
    public static function service(array $page, array $settings): array
    {
        $service = $page['service'];
        $locale = Locales::current();
        $hero = $service['heroImage'];
        $alternates = array_map(fn (string $path): string => self::baseUrl().$path, $page['paths']);
        $description = $service['seoDescription'] ?? $service['intro'] ?? $service['description'] ?? $settings['seo']['description'];

        return [
            'title' => (string) ($service['seoTitle'] ?? $service['title'].' | '.$settings['companyName']),
            'description' => Str::limit(trim(strip_tags((string) $description)), 300, ''),
            'canonical' => $alternates[$locale],
            'alternates' => $alternates,
            'ogLocale' => Locales::ogLocale($locale),
            'ogAlternates' => array_values(array_map(Locales::ogLocale(...), array_diff(Locales::all(), [$locale]))),
            'siteName' => (string) $settings['companyName'],
            'image' => $hero['large'] ?? $settings['seo']['image'],
            'imageAlt' => (string) ($hero['alt'] ?? $service['title']),
            'preload' => $hero,
            'themeColor' => (string) $settings['colors']['brand'],
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @param  array<string, mixed>  $settings
     * @param  array<string, string>  $paths
     * @return SeoMeta
     */
    public static function blogPost(array $page, array $settings, array $paths): array
    {
        $locale = Locales::current();
        $alternates = array_map(fn (string $path): string => self::baseUrl().$path, $paths);
        $description = $page['seoDescription'] ?? $page['excerpt'] ?? $settings['seo']['description'];

        return [
            'title' => (string) ($page['seoTitle'] ?? $page['title'].' | '.$settings['companyName']),
            'description' => Str::limit(trim(strip_tags((string) $description)), 300, ''),
            'canonical' => $alternates[$locale],
            'alternates' => $alternates,
            'ogLocale' => Locales::ogLocale($locale),
            'ogAlternates' => array_values(array_map(Locales::ogLocale(...), array_diff(Locales::all(), [$locale]))),
            'siteName' => (string) $settings['companyName'],
            'image' => $page['image']['large'] ?? $settings['seo']['image'],
            'imageAlt' => (string) ($page['image']['alt'] ?? $page['title']),
            'preload' => $page['image'],
            'themeColor' => (string) $settings['colors']['brand'],
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
