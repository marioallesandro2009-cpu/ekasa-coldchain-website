<?php

namespace App\Http\Controllers;

use App\Models\HeroSlide;
use App\Models\HeroStat;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\TrustLogo;
use App\Support\FrontendData;
use App\Support\Locales;
use App\Support\Seo;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * robots.txt, sitemap.xml and llms.txt, generated from the CMS data.
 */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            '# Search engines and AI crawlers are welcome.',
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
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
            HeroSlide::query()->max('updated_at'),
            HeroStat::query()->max('updated_at'),
            PageSection::query()->max('updated_at'),
            TrustLogo::query()->max('updated_at'),
        ])->filter()->map(fn ($date) => Carbon::parse($date))->max() ?? now();

        // Every page in every language, each listing all its language versions.
        $pages = ['/' => '1.0'];
        $urls = [];

        foreach ($pages as $page => $priority) {
            $alternates = Locales::alternates($page);

            foreach ($alternates as $url) {
                $urls[] = ['loc' => $url, 'lastmod' => $lastModified->toAtomString(), 'priority' => $priority, 'alternates' => $alternates];
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
            'baseUrl' => Seo::baseUrl(),
            'alternates' => Locales::alternates(),
        ])->render());
    }

    private function text(string $content): Response
    {
        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
