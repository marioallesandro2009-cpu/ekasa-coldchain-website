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
     * Service detail page: Organization, WebPage, the Service, the breadcrumb
     * (Home > Services > service) and, when the page has FAQ blocks, an FAQPage.
     *
     * @param  array{service: array<string, mixed>, faqs: list<array{question: string, answer: string}>}  $page
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    public static function service(array $page, array $settings, array $seo): array
    {
        $base = Seo::baseUrl();
        $locale = Locales::current();
        $url = (string) $seo['canonical'];
        $orgId = $base.'/#organization';
        $service = $page['service'];
        $homeUrl = Locales::url($locale);

        $graph = [
            self::organization($settings, $orgId, $base),
            [
                '@type' => 'WebPage',
                '@id' => $url.'#webpage',
                'url' => $url,
                'name' => $seo['title'],
                'description' => $seo['description'],
                'inLanguage' => $locale,
                'isPartOf' => ['@id' => $base.'/#website'],
                'breadcrumb' => ['@id' => $url.'#breadcrumb'],
                'primaryImageOfPage' => $seo['image'] ? ['@type' => 'ImageObject', 'url' => $seo['image']['url']] : null,
                'dateModified' => $service['updatedAt'],
            ],
            [
                '@type' => 'Service',
                '@id' => $url.'#service',
                'name' => $service['title'],
                'serviceType' => $service['title'],
                'description' => $service['intro'] ?? $service['description'],
                'url' => $url,
                'image' => $seo['image']['url'] ?? null,
                'provider' => ['@id' => $orgId],
                'areaServed' => self::area($settings),
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => $url.'#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('Home'), 'item' => $homeUrl],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => __('Services'), 'item' => $homeUrl.'#'.PageSection::SERVICES],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $service['title'], 'item' => $url],
                ],
            ],
        ];

        if ($page['faqs'] !== []) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $url.'#faq',
                'mainEntity' => array_map(fn (array $faq): array => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                ], $page['faqs']),
            ];
        }

        return self::withoutEmpty(['@context' => 'https://schema.org', '@graph' => $graph]);
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
                    'url' => ($service['url'] ?? null) ? Seo::baseUrl().$service['url'] : $pageUrl.'#'.$service['anchor'],
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
