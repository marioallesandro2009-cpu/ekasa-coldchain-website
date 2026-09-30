<?php

namespace App\Support;

use App\Models\PageSection;

/**
 * JSON-LD for a blog post's own detail page, kept separate from
 * App\Support\StructuredData so this feature never has to touch that file.
 * There is no blog index page (one-page site: the #blog section on the home
 * page covers that), so there is no index() graph here.
 */
final class BlogStructuredData
{
    /**
     * @param  array<string, mixed>  $page
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    public static function post(array $page, array $settings, array $seo): array
    {
        $base = Seo::baseUrl();
        $locale = Locales::current();
        $url = (string) $seo['canonical'];
        $orgId = $base.'/#organization';
        $homeUrl = Locales::url($locale);
        $blogSectionUrl = $homeUrl.'#'.PageSection::BLOG;

        $graph = [
            self::organization($settings, $orgId),
            [
                '@type' => 'WebPage',
                '@id' => $url.'#webpage',
                'url' => $url,
                'name' => $seo['title'],
                'description' => $seo['description'],
                'inLanguage' => $locale,
                'primaryImageOfPage' => $seo['image'] ? ['@type' => 'ImageObject', 'url' => $seo['image']['url']] : null,
                'dateModified' => $page['updatedAt'],
                'breadcrumb' => ['@id' => $url.'#breadcrumb'],
            ],
            array_filter([
                '@type' => 'BlogPosting',
                '@id' => $url.'#article',
                'headline' => $page['title'],
                'description' => $seo['description'],
                'url' => $url,
                'image' => $seo['image']['url'] ?? null,
                'datePublished' => $page['publishedAt'],
                'dateModified' => $page['updatedAt'],
                'author' => ['@id' => $orgId],
                'publisher' => ['@id' => $orgId],
                'mainEntityOfPage' => ['@id' => $url.'#webpage'],
            ]),
            [
                '@type' => 'BreadcrumbList',
                '@id' => $url.'#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('Home'), 'item' => $homeUrl],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => __('Blog'), 'item' => $blogSectionUrl],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $page['title'], 'item' => $url],
                ],
            ],
        ];

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private static function organization(array $settings, string $orgId): array
    {
        return array_filter([
            '@type' => 'Organization',
            '@id' => $orgId,
            'name' => $settings['companyName'] ?? null,
            'url' => Seo::baseUrl().'/',
            'logo' => $settings['logoColor']['url'] ?? null,
            'email' => $settings['email'] ?? null,
            'telephone' => $settings['phone'] ?? null,
            'sameAs' => $settings['sameAs'] ?? null,
        ]);
    }
}
