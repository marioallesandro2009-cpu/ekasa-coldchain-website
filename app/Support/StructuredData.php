<?php

namespace App\Support;

use App\Models\PageSection;

/**
 * schema.org JSON-LD for the public site, as one @graph.
 *
 * Nodes reference each other by @id, so later stages can add a LocalBusiness
 * per location (see locations()) that points back to the Organization.
 */
final class StructuredData
{
    /**
     * @param  array{settings: array<string, mixed>, services: list<array<string, mixed>>}  $data
     * @return array<string, mixed>
     */
    public static function home(array $data): array
    {
        $settings = $data['settings'];
        $base = Seo::baseUrl();
        $locale = Locales::current();
        $pageUrl = Locales::url($locale);
        $orgId = $base.'/#organization';
        $websiteId = $base.'/#website';

        $graph = [
            self::organization($settings, $orgId, $base),
            [
                '@type' => 'WebSite',
                '@id' => $websiteId,
                'url' => $base.'/',
                'name' => $settings['companyName'],
                'inLanguage' => Locales::all(),
                'publisher' => ['@id' => $orgId],
            ],
            [
                '@type' => 'WebPage',
                '@id' => $pageUrl.'#webpage',
                'url' => $pageUrl,
                'name' => $settings['seo']['title'],
                'description' => $settings['seo']['description'],
                'inLanguage' => $locale,
                'isPartOf' => ['@id' => $websiteId],
                'about' => ['@id' => $orgId],
            ],
        ];

        if ($data['services'] !== []) {
            $graph[] = self::services($data, $orgId, $pageUrl);
        }

        return self::withoutEmpty([
            '@context' => 'https://schema.org',
            '@graph' => [...$graph, ...self::locations($orgId)],
        ]);
    }

    /**
     * Drops empty values (null, '' and empty arrays) at every level, so fields
     * such as telephone, email or sameAs are left out instead of being empty.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function withoutEmpty(array $data): array
    {
        $isList = array_is_list($data);
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::withoutEmpty($value);
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $clean[$key] = $value;
        }

        return $isList ? array_values($clean) : $clean;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private static function organization(array $settings, string $id, string $base): array
    {
        $logo = $settings['logoColor'] ?? $settings['logoWhite'];

        return [
            '@type' => 'Organization',
            '@id' => $id,
            'name' => $settings['companyName'],
            'legalName' => $settings['legalName'],
            'url' => $base.'/',
            'logo' => $logo ? [
                '@type' => 'ImageObject',
                'url' => $logo['url'],
                'width' => $logo['width'],
                'height' => $logo['height'],
            ] : null,
            'description' => $settings['companyDescription'] ?: $settings['seo']['description'],
            'telephone' => $settings['phone'],
            'email' => $settings['email'],
            'sameAs' => $settings['sameAs'],
            'areaServed' => self::area($settings),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, string>|null
     */
    private static function area(array $settings): ?array
    {
        return $settings['areaServed'] ? ['@type' => 'Country', 'name' => $settings['areaServed']] : null;
    }

    /**
     * @param  array{settings: array<string, mixed>, services: list<array<string, mixed>>, sections?: list<array<string, mixed>>}  $data
     * @return array<string, mixed>
     */
    private static function services(array $data, string $orgId, string $pageUrl): array
    {
        $services = $data['services'];
        $area = self::area($data['settings']);
        $section = collect($data['sections'] ?? [])->firstWhere('id', PageSection::SERVICES);

        return [
            '@type' => 'ItemList',
            '@id' => $pageUrl.'#services',
            'name' => $section['navLabel'] ?? __('Services'),
            'itemListElement' => array_map(fn (array $service, int $i): array => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => [
                    '@type' => 'Service',
                    'name' => $service['title'],
                    'description' => $service['description'],
                    'serviceType' => $service['title'],
                    'url' => $pageUrl.'#'.$service['anchor'],
                    'provider' => ['@id' => $orgId],
                    'areaServed' => $area,
                ],
            ], $services, array_keys($services)),
        ];
    }

    /**
     * LocalBusiness nodes per location (stage "Locations"). Each node should use
     * '@type' => 'LocalBusiness', its own '@id', address/geo, and
     * 'parentOrganization' => ['@id' => $orgId].
     *
     * @return list<array<string, mixed>>
     */
    private static function locations(string $orgId): array
    {
        return [];
    }
}
