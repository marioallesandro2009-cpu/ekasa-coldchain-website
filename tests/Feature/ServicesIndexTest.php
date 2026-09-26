<?php

use App\Models\Service;

/**
 * REDESIGN MATANG, Bagian A: the services index page (/services, /id/layanan) is
 * a real page registered before the {slug} detail route, not a slug lookup.
 */
beforeEach(fn () => seedSite());

it('serves the services index at /services and /id/layanan, not the {slug} route', function () {
    // If the index route were registered after {slug}, or missing, this would
    // 404 (no service has "services"/"layanan" as its slug) instead of 200.
    $this->get('/services')->assertOk()->assertSee('<h1', false);
    $this->get('/id/layanan')->assertOk()->assertSee('<h1', false);

    expect(Service::query()->where('slug', 'services')->exists())->toBeFalse();
});

it('lists every published featured service on the index, each linking to its own detail page', function () {
    $response = $this->get('/services')->assertOk();

    foreach (Service::query()->active()->where('is_featured', true)->where('detail_status', Service::PUBLISHED)->get() as $service) {
        $response->assertSee($service->translate('title'))
            ->assertSee($service->detailPath('en'), false);
    }
});

it('never links a draft service to its own (unpublished) detail page', function () {
    // Draft services still appear (as a card linking to contact, same convention as
    // the mega menu and the home page teaser) — only the URL to the detail page itself
    // must stay absent until it is published.
    $draft = Service::query()->where('detail_status', Service::DRAFT)->firstOrFail();

    $this->get('/services')->assertOk()->assertDontSee($draft->detailPath('en'), false);
});

it('gives the index its own breadcrumb JSON-LD (Home > Services, no third crumb)', function () {
    $html = $this->get('/services')->assertOk()->getContent();
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);
    $jsonLd = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);

    $breadcrumb = collect($jsonLd['@graph'])->firstWhere('@type', 'BreadcrumbList');

    expect($breadcrumb['itemListElement'])->toHaveCount(2)
        ->and($breadcrumb['itemListElement'][1]['name'])->toBe('Services');
});

it('sends the mega menu, navbar and detail-page breadcrumb "Services" link to the index page', function () {
    $this->get('/services')->assertOk();
    $home = $this->get('/services')->assertOk()->getContent();
    expect($home)->toContain('href="/services"');

    $detail = Service::query()->where('detail_status', Service::PUBLISHED)->firstOrFail();
    $this->get($detail->detailPath('en'))->assertOk()->assertSee('href="/services"', false);
});
