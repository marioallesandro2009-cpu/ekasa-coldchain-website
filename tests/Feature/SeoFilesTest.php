<?php

use App\Models\PageSection;
use App\Models\SiteSetting;
use App\Support\FrontendData;

beforeEach(fn () => seedSite());

it('serves robots.txt allowing crawlers and linking the sitemap', function () {
    $response = $this->get('/robots.txt')->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/plain')
        ->and($response->getContent())
        ->toContain("User-agent: *\nAllow: /")
        ->toContain('Disallow: /admin')
        ->toContain('Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml')
        ->not->toContain('GPTBot');
});

it('serves a valid sitemap.xml', function () {
    $response = $this->get('/sitemap.xml')->assertOk();

    $xml = simplexml_load_string($response->getContent());

    expect($response->headers->get('Content-Type'))->toContain('application/xml')
        ->and((string) $xml->url[0]->loc)->toBe(rtrim(config('app.url'), '/').'/')
        ->and((string) $xml->url[0]->lastmod)->not->toBeEmpty();
});

it('serves llms.txt with the company summary and services', function () {
    $content = $this->get('/llms.txt')->assertOk()->getContent();

    expect($content)
        ->toStartWith('# PT Ekasa Cold Chain')
        ->toContain('## Services')
        ->toContain('- [Cold Storage](')
        ->toContain('Chilled and frozen rooms')
        ->toContain('## Contact');
});

it('takes the company name, contacts and domain only from the settings and APP_URL', function () {
    config(['app.url' => 'https://acme.example']);
    SiteSetting::current()->update([
        'company_name' => 'Acme Frozen',
        'legal_name' => 'PT Acme Frozen Indonesia',
        'company_description' => 'Acme Frozen keeps goods cold.',
        'seo_title' => 'Acme Frozen',
        'seo_description' => 'Acme Frozen cold chain.',
        'email' => 'hello@acme.example',
        'phone' => '+62 21 0000 0000',
    ]);
    // Section titles are admin content too (the seeded About title names the company).
    PageSection::query()->where('key', 'about')->firstOrFail()->update(['title' => ['en' => 'About Acme Frozen', 'id' => 'Tentang Acme Frozen']]);
    FrontendData::flush();

    $pages = [
        $this->get('/')->getContent(),
        $this->get('/llms.txt')->getContent(),
        $this->get('/sitemap.xml')->getContent(),
        $this->get('/robots.txt')->getContent(),
    ];

    expect($pages[0])->toContain('&copy; '.now()->year.' PT Acme Frozen Indonesia.')->toContain('"name":"Acme Frozen"')
        ->and($pages[1])->toStartWith('# Acme Frozen')->toContain('hello@acme.example');

    foreach ($pages as $content) {
        expect($content)->not->toMatch('/ekasa|coldspace/i')
            ->and($content)->not->toContain('https://coldspace.id');
    }
});

it('shows no third-party brand in the public pages and uses the legal name for the copyright and legalName', function () {
    foreach (['/', '/llms.txt', '/sitemap.xml', '/robots.txt'] as $url) {
        expect(strtolower($this->get($url)->getContent()))->not->toContain('coldspace');
    }

    $html = $this->get('/')->getContent();

    expect($html)->toContain('&copy; '.now()->year.' PT Ekasa Cold Chain.')
        ->toContain('"legalName":"PT Ekasa Cold Chain"')
        ->toContain('"name":"PT Ekasa Cold Chain"');

    SiteSetting::current()->update(['company_name' => 'Ekasa', 'legal_name' => null]);
    FrontendData::flush();

    expect($this->get('/')->getContent())->toContain('&copy; '.now()->year.' Ekasa.');
});
