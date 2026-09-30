<?php

namespace App\Http\Controllers;

use App\Models\HeroSlide;
use App\Models\HeroStat;
use App\Models\Location;
use App\Models\PageSection;
use App\Models\Post;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\TrustLogo;
use App\Support\FrontendData;
use App\Support\Locales;
use App\Support\Seo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * robots.txt, sitemap.xml, llms.txt and site.webmanifest, generated from the CMS data.
 */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            '# Search engines and AI crawlers are welcome.',
            'User-agent: *',
            'Allow: /',
            // A custom ADMIN_PATH is not announced here (that would publish it); the admin pages
            // carry "X-Robots-Tag: noindex" instead (SecurityHeaders).
            ...(config('security.admin_path') === 'admin' ? ['Disallow: /admin'] : []),
            'Disallow: /livewire',
            '',
            'Sitemap: '.Seo::baseUrl().'/sitemap.xml',
        ];

        return $this->text(implode("\n", $lines)."\n");
    }

    public function sitemap(): Response
    {
        $lastModified = collect([
            SiteSetting::query()->max('updated_at'),
            Service::query()->max('updated_at'),
            Location::query()->max('updated_at'),
            HeroSlide::query()->max('updated_at'),
            HeroStat::query()->max('updated_at'),
            PageSection::query()->max('updated_at'),
            TrustLogo::query()->max('updated_at'),
            Post::query()->max('updated_at'),
        ])->filter()->map(fn ($date) => Carbon::parse($date))->max() ?? now();

        // Every page in every language, each listing all its language versions.
        // The home page's own entry covers #blog too (one-page site: no separate
        // blog index route) — only a post's own detail page gets its own entry.
        $pages = ['/' => '1.0'];
        $urls = [];

        foreach ($pages as $page => $priority) {
            $alternates = Locales::alternates($page);

            foreach ($alternates as $url) {
                $urls[] = ['loc' => $url, 'lastmod' => $lastModified->toAtomString(), 'priority' => $priority, 'alternates' => $alternates];
            }
        }

        // Services index (/services, /id/layanan): the segment differs per language,
        // so it cannot use Locales::alternates() like the pages above.
        $indexAlternates = [];

        foreach (Locales::all() as $locale) {
            $indexAlternates[$locale] = Seo::baseUrl().Service::indexPath($locale);
        }

        foreach ($indexAlternates as $url) {
            $urls[] = ['loc' => $url, 'lastmod' => $lastModified->toAtomString(), 'priority' => '0.9', 'alternates' => $indexAlternates];
        }

        // Published service pages: their own slug in each language, their own lastmod
        // (shared data such as FAQ or fleet changes are not tracked per page).
        foreach (Service::query()->active()->where('detail_status', Service::PUBLISHED)->ordered()->get() as $service) {
            $alternates = [];

            foreach (Locales::all() as $locale) {
                $alternates[$locale] = Seo::baseUrl().$service->detailPath($locale);
            }

            foreach ($alternates as $url) {
                $urls[] = ['loc' => $url, 'lastmod' => ($service->updated_at ?? now())->toAtomString(), 'priority' => '0.8', 'alternates' => $alternates];
            }
        }

        // Published blog posts: their own slug in each language, their own lastmod.
        foreach (Post::query()->active()->where('status', Post::PUBLISHED)->get() as $post) {
            $alternates = [];

            foreach (Locales::all() as $locale) {
                $alternates[$locale] = Seo::baseUrl().$post->detailPath($locale);
            }

            foreach ($alternates as $url) {
                $urls[] = ['loc' => $url, 'lastmod' => ($post->updated_at ?? now())->toAtomString(), 'priority' => '0.6', 'alternates' => $alternates];
            }
        }

        $xml = view('seo.sitemap', ['urls' => $urls, 'defaultLocale' => Locales::default()])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * Plain-language summary for LLM-based crawlers (https://llmstxt.org).
     */
    public function llms(): Response
    {
        $data = FrontendData::all();

        return $this->text(view('seo.llms', [
            'settings' => $data['settings'],
            'services' => $data['services'],
            'posts' => Post::query()->active()->where('status', Post::PUBLISHED)->orderByDesc('published_at')->take(20)->get()
                ->map->toFrontend()->values()->all(),
            'baseUrl' => Seo::baseUrl(),
            'alternates' => Locales::alternates(),
        ])->render());
    }

    /**
     * Web app manifest: name, favicon and the brand green for the browser UI.
     */
    public function manifest(): JsonResponse
    {
        $settings = FrontendData::all()['settings'];
        $favicon = $settings['favicon'];

        return response()->json([
            'name' => $settings['companyName'],
            'short_name' => $settings['companyName'],
            'start_url' => '/',
            'display' => 'browser',
            'theme_color' => $settings['colors']['brand'],
            'background_color' => $settings['colors']['brand'],
            'icons' => $favicon ? array_values(array_filter([
                $favicon['sizes'][192] !== $favicon['url'] ? ['src' => $favicon['sizes'][192], 'type' => 'image/png', 'sizes' => '192x192'] : null,
                ['src' => $favicon['url'], 'type' => $favicon['mime'], 'sizes' => $favicon['width'] > 0 ? "{$favicon['width']}x{$favicon['height']}" : 'any'],
            ])) : [],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function text(string $content): Response
    {
        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
