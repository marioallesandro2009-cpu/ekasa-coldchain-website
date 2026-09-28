<?php

use App\Models\Location;
use App\Support\FrontendData;

/**
 * Tahap 4: the home page's #locations section — an auto-fitting Leaflet map
 * kept in sync with a filterable card list. One-page/parallax site (same as
 * #services above it): this is a section on "/", not a separate route.
 */
beforeEach(fn () => seedSite());

it('renders the #locations section on the home page with its own heading', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('id="locations"')
        ->toContain('id="locations-title"');
});

it('sends the navbar "Locations" link to the home page anchor, not a separate route', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('href="#locations"')
        ->not->toContain('href="/locations"');
});

it('lists every active location with its name and a Get Directions link', function () {
    $response = $this->get('/')->assertOk();

    foreach (Location::query()->active()->get() as $location) {
        $response->assertSee($location->name)
            ->assertSee(e($location->mapsUrl()), false);
    }
});

it('computes its stats from the active locations, not a fixed number', function () {
    $before = $this->get('/')->assertOk()->getContent();
    $section = substr($before, (int) strpos($before, 'id="locations"'));
    preg_match('/<dd[^>]*>\s*<span class="sr-only">(\d+)/', $section, $match);
    $countBefore = (int) $match[1];

    expect($countBefore)->toBe(Location::query()->active()->count());

    Location::query()->create([
        'name' => 'Extra Warehouse', 'kind' => 'warehouse', 'city' => 'Cikarang',
        'lat' => -6.26, 'lng' => 107.15, 'is_active' => true,
    ]);
    FrontendData::flush();

    $after = $this->get('/')->assertOk()->getContent();
    $sectionAfter = substr($after, (int) strpos($after, 'id="locations"'));
    preg_match('/<dd[^>]*>\s*<span class="sr-only">(\d+)/', $sectionAfter, $match);

    expect((int) $match[1])->toBe($countBefore + 1);
});

it('shows a plain empty message, without error, when no location is active', function () {
    Location::query()->update(['is_active' => false]);
    FrontendData::flush();

    $this->get('/')->assertOk()
        ->assertSee('No locations yet.')
        ->assertDontSee('location-card', false);
});

it('leaves out empty fields (zones, hours) instead of rendering them blank', function () {
    $minimal = Location::query()->create([
        'name' => 'Minimal Hub', 'kind' => 'hub', 'city' => 'Depok', 'is_active' => true,
        // lat/lng, temperature_zones, opening_hours, commodities, phone: all left empty.
    ]);
    FrontendData::flush();

    $html = $this->get('/')->assertOk()->getContent();
    $card = substr($html, (int) strpos($html, 'id="location-'.$minimal->id.'"'));
    $card = substr($card, 0, (int) strpos($card, '</li>'));

    expect($card)->not->toContain('null')
        ->not->toContain('undefined')
        ->toContain('Minimal Hub');
});

it('gives the home page a LocalBusiness node per active location', function () {
    $html = $this->get('/')->assertOk()->getContent();
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);
    $jsonLd = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);

    $businesses = collect($jsonLd['@graph'])->where('@type', 'LocalBusiness');

    expect($businesses)->toHaveCount(Location::query()->active()->count());
});

it('does not include opening_hours in LocalBusiness JSON-LD (freeform text, not schema.org structured hours)', function () {
    $html = $this->get('/')->assertOk()->getContent();
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);
    $jsonLd = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);

    collect($jsonLd['@graph'])->where('@type', 'LocalBusiness')->each(
        fn (array $node) => expect($node)->not->toHaveKey('openingHoursSpecification')
    );
});
