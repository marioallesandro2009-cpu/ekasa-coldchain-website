<?php

use App\Models\Faq;
use App\Models\Service;
use App\Models\User;
use App\Support\Seo;
use App\Support\ServiceBlocks;

/**
 * Tahap 3, Bagian C: service detail pages (template and common blocks).
 */
beforeEach(fn () => seedSite());

function base(): string
{
    return Seo::baseUrl();
}

function serviceBySlug(string $slug): Service
{
    return Service::query()->where('slug', $slug)->firstOrFail();
}

/**
 * @return array<string, mixed>
 */
function jsonLdOf(string $html): array
{
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);

    return json_decode($match[1] ?? '', true, 512, JSON_THROW_ON_ERROR);
}

it('serves every published service page in both languages', function (string $url, string $title) {
    $this->get($url)->assertOk()->assertSee('<h1', false)->assertSee($title);
})->with([
    ['/services/cold-storage', 'Cold Storage'],
    ['/id/layanan/penyimpanan-dingin', 'Cold Storage'],
    ['/services/linehaul-transport', 'Linehaul Transport'],
    ['/id/layanan/transportasi-linehaul', 'Transportasi Linehaul'],
    ['/services/hyperlocal-fulfilment', 'Hyperlocal Fulfilment'],
    ['/id/layanan/hyperlocal-fulfilment', 'Hyperlocal Fulfilment'],
    ['/services/logistic-partnership', 'Logistic Partnership'],
    ['/id/layanan/kemitraan-logistik', 'Kemitraan Logistik'],
]);

it('redirects a slug of the other language and 404s unknown pages', function () {
    $this->get('/id/layanan/cold-storage')->assertRedirect('/id/layanan/penyimpanan-dingin')->assertStatus(301);
    $this->get('/services/penyimpanan-dingin')->assertRedirect('/services/cold-storage');
    $this->get('/services/does-not-exist')->assertNotFound();
});

it('hides a draft page from visitors and shows admins a noindex preview', function () {
    $this->get('/services/omnichannel-integration')->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->get('/services/omnichannel-integration')
        ->assertOk()
        ->assertSee('Omnichannel Integration')
        ->assertSee('Preview (draft)')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertDontSee('application/ld+json', false);
});

it('links the language versions with hreflang, canonical and the language switch', function () {
    $html = $this->get('/services/cold-storage')->getContent();

    expect($html)
        ->toContain('<link rel="canonical" href="'.base().'/services/cold-storage">')
        ->toContain('<link rel="alternate" hreflang="id" href="'.base().'/id/layanan/penyimpanan-dingin">')
        ->toContain('<link rel="alternate" hreflang="x-default" href="'.base().'/services/cold-storage">')
        ->toContain('href="/id/layanan/penyimpanan-dingin"')
        // Home sections from another page: back to the home page.
        ->toContain('href="/#contact"');

    $this->get('/id/layanan/penyimpanan-dingin')->assertSee('href="/services/cold-storage"', false)->assertSee('href="/id#contact"', false);
});

it('outputs valid JSON-LD with Service, BreadcrumbList and FAQPage', function () {
    $types = array_column(jsonLdOf($this->get('/services/cold-storage')->getContent())['@graph'], '@type');

    expect($types)->toContain('Organization', 'WebPage', 'Service', 'BreadcrumbList', 'FAQPage');

    $graph = collect(jsonLdOf($this->get('/id/layanan/penyimpanan-dingin')->getContent())['@graph']);
    expect($graph->firstWhere('@type', 'BreadcrumbList')['itemListElement'][0]['name'])->toBe('Beranda')
        ->and($graph->firstWhere('@type', 'FAQPage')['mainEntity'][0]['name'])->toBe('Rentang suhu apa saja yang tersedia?');
});

it('lists the detail pages in the sitemap with hreflang', function () {
    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain('<loc>'.base().'/services/linehaul-transport</loc>')
        ->toContain('<loc>'.base().'/id/layanan/transportasi-linehaul</loc>')
        ->toContain('hreflang="id" href="'.base().'/id/layanan/transportasi-linehaul"')
        ->not->toContain('omnichannel-integration</loc>');
});

it('links the mega menu, home cards and llms.txt to the detail pages', function () {
    $this->get('/')->assertSee('href="/services/cold-storage"', false);
    $this->get('/id')->assertSee('href="/id/layanan/penyimpanan-dingin"', false);
    $this->get('/llms.txt')->assertSee('('.base().'/services/cold-storage)', false);
});

it('renders the common blocks with splitText headings and an animated FAQ that works without JS', function () {
    $html = $this->get('/services/cold-storage')->getContent();

    expect($html)
        ->toContain('id="pita-spesifikasi"')
        ->toContain('id="teks-gambar"')
        ->toContain('id="keunggulan"')
        ->toContain('id="industri"')
        ->toContain('<details')
        ->toContain('What temperature ranges do you offer?')
        ->toContain('x-data="animatedList" data-animated-list')
        ->toContain('Tell us what needs to stay cold')
        ->and(substr_count($html, 'x-data="splitText"'))->toBeGreaterThanOrEqual(6);
});

it('skips hidden and empty blocks', function () {
    $service = serviceBySlug('cold-storage');
    $blocks = collect($service->blocks)->map(function (array $block): array {
        if ($block['type'] === 'keunggulan') {
            $block['data']['hidden'] = true;
        }

        if ($block['type'] === 'faq') {
            $block['data']['faq_ids'] = [];
        }

        return $block;
    })->all();
    $service->update(['blocks' => $blocks]);

    $html = $this->get('/services/cold-storage')->getContent();
    expect($html)->not->toContain('id="keunggulan"')
        ->not->toContain('id="faq"')
        ->not->toContain('"FAQPage"');
});

it('shows the Indonesian block texts only when the block is checked', function () {
    $this->get('/id/layanan/penyimpanan-dingin')->assertSee('Setiap palet punya tempat dan suhunya');

    $service = serviceBySlug('cold-storage');
    $service->update(['blocks' => collect($service->blocks)->map(function (array $block): array {
        if ($block['type'] === 'teks_gambar') {
            $block['data']['id_reviewed'] = false;
        }

        return $block;
    })->all()]);

    $this->get('/id/layanan/penyimpanan-dingin')
        ->assertDontSee('Setiap palet punya tempat dan suhunya')
        ->assertSee('Every pallet has its place and its temperature');
});

it('gives a new service from the admin the same template, with a title even without a hero block', function () {
    $service = Service::query()->create([
        'title' => ['en' => 'Cross Docking', 'id' => 'Cross Docking'],
        'slug' => 'cross-docking',
        'intro' => ['en' => 'Goods move from truck to truck without storage.'],
        'icon' => 'box',
        'detail_status' => Service::PUBLISHED,
        'blocks' => array_values(array_filter(ServiceBlocks::defaultLayout(), fn (array $block): bool => $block['type'] !== 'hero_layanan')),
        'is_active' => true,
    ]);

    expect($service->slugs)->toBe(['en' => 'cross-docking', 'id' => 'cross-docking']);

    $this->get('/services/cross-docking')->assertOk()
        ->assertSee('<h1', false)
        ->assertSee('Goods move from truck to truck without storage.')
        // The empty template blocks are not rendered.
        ->assertDontSee('data-animated-list', false);
});

it('updates the cached page when admin data changes', function () {
    $this->get('/services/cold-storage')->assertSee('What temperature ranges do you offer?');

    Faq::query()->where('question->en', 'What temperature ranges do you offer?')->firstOrFail()
        ->update(['question' => ['en' => 'Which temperatures can you hold?']]);

    $this->get('/services/cold-storage')->assertSee('Which temperatures can you hold?');
});
