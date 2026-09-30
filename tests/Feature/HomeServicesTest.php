<?php

use App\Models\Service;
use App\Support\FrontendData;

/**
 * Tahap 3, Bagian B: the services section of the home page.
 */
beforeEach(fn () => seedSite(serviceImages: true));

it('shows only the featured services as cards, no supporting ribbon', function () {
    $html = $this->get('/')->assertOk()->getContent();
    // Bounded to just this section (id="services" .. id="locations"): further
    // down the page the #blog section has its own, unrelated "Learn more"
    // links, and an unbounded substr would see those too.
    $servicesStart = (int) strpos($html, 'id="services"');
    $section = substr($html, $servicesStart, strpos($html, 'id="locations"') - $servicesStart);

    // Teaser (TAHAP 3, Bagian 2): compact cards (number + title + photo only, no
    // key points or "Learn more" text) plus a link to the full /services index.
    // A pure teaser — no supporting/Pendukung ribbon here at all (unlike the full
    // /services index); Omnichannel (not featured) is only ever reached from there.
    expect(substr_count($section, '<article'))->toBe(4)
        ->and($section)->toContain('Cold Storage')
        ->not->toContain('Three temperature zones')
        ->not->toContain('Learn more')
        ->toContain('>01<')
        ->toContain('View all services')
        ->toContain('href="/services"')
        ->not->toContain('Also available')
        ->not->toContain('Omnichannel Integration')
        ->toContain('x-data="splitText" data-split-text="h2"')
        ->toContain('x-data="spotlightCard"');
});

it('shows the Indonesian texts on /id', function () {
    $this->get('/id')->assertOk()
        ->assertSee('Layanan Kami')
        ->assertDontSee('Tiga zona suhu')
        ->assertSee('Lihat semua layanan')
        ->assertDontSee('Juga tersedia');
});

it('uses the fleet side view cropped around its focus point for the Linehaul card', function () {
    $linehaul = collect(FrontendData::services('en'))->firstWhere('title', 'Linehaul Transport');

    expect($linehaul['image']['focus'])->toBe('70% 50%')
        ->and($linehaul['image']['large']['url'])->toContain('fleet-fe71-long-side');

    $this->get('/')->assertSee('style="object-position: 70% 50%"', false);
});

it('keeps at most three key points and links to its own anchor while the detail page is a draft', function () {
    Service::query()->where('slug', 'cold-storage')->firstOrFail()
        ->update(['key_points' => ['en' => "One\nTwo\nThree\nFour"], 'detail_status' => Service::DRAFT]);

    $cold = collect(FrontendData::services('en'))->firstWhere('title', 'Cold Storage');

    expect($cold['keyPoints'])->toBe(['One', 'Two', 'Three'])
        ->and($cold['url'])->toBeNull();

    // Links::service(): no published page yet, so the card falls back to the
    // service's own home anchor (consistent with the mega menu and mobile menu),
    // not the unrelated "#contact" anchor it used to hand-roll.
    $this->get('/')->assertSee('href="#cold-storage" class="focus-visible:outline-none', false);
});

it('lays the main services out as a bento grid: the first large, three beside it', function () {
    $html = $this->get('/')->getContent();
    // Bounded to just this section: #about further down the page reuses the
    // same bento CSS classes for its own value cards (up to 3, same shape),
    // and an unbounded count would add those in too.
    $servicesStart = (int) strpos($html, 'id="services"');
    $section = substr($html, $servicesStart, strpos($html, 'id="locations"') - $servicesStart);

    expect($section)->toContain('lg:col-span-7 lg:row-span-3')
        ->and(substr_count($section, 'lg:col-span-5'))->toBe(3)
        ->and($section)->not->toContain('lg:col-span-4');
});

it('never grows past 4 cards on the home teaser: a 5th featured service is capped away, not added below', function () {
    Service::query()->create(['title' => ['en' => 'Cross Docking'], 'slug' => 'cross-docking', 'icon' => 'box', 'is_featured' => true, 'is_active' => true]);

    $html = $this->get('/')->getContent();
    $section = substr($html, (int) strpos($html, 'id="services"'));

    // Unlike /services (unbounded), the home teaser stays at the shared,
    // capped featuredServices — the 5th featured service never gets a
    // "more" row here, it only ever shows up on the full index.
    expect(substr_count($section, '<article'))->toBe(4)
        ->and($section)->not->toContain('lg:col-span-4')
        ->not->toContain('Cross Docking');
});

it('makes the first main service in the admin order the large card', function () {
    $section = fn (): string => substr($html = $this->get('/')->getContent(), (int) strpos($html, 'id="services"'));
    // The large card's wrapper class ("lg:col-span-7") sits right before its title
    // in the markup; the next "lg:col-span-5" starts the first side card.
    $largeSlice = fn (string $html): string => substr($html, (int) strpos($html, 'lg:col-span-7'), (int) strpos($html, 'lg:col-span-5') - (int) strpos($html, 'lg:col-span-7'));

    expect($largeSlice($section()))->toContain('Cold Storage')->not->toContain('Linehaul Transport');

    // Through the model, like the admin (a query update would skip the cache flush).
    Service::query()->where('slug', 'linehaul-transport')->firstOrFail()->update(['sort_order' => 0]);

    $html = $section();
    expect(strpos($html, 'Linehaul Transport'))->toBeLessThan(strpos($html, 'Cold Storage'))
        ->and($largeSlice($html))->toContain('Linehaul Transport')->not->toContain('Cold Storage');
});
