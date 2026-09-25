<?php

use App\Models\Service;
use App\Support\FrontendData;

/**
 * Tahap 3, Bagian B: the services section of the home page.
 */
beforeEach(fn () => seedSite(serviceImages: true));

it('shows the main services as cards and the supporting ones as ribbons', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $section = substr($html, (int) strpos($html, 'id="services"'));

    expect(substr_count($section, '<article'))->toBe(4)
        ->and($section)->toContain('Cold Storage')
        ->toContain('Three temperature zones')
        ->toContain('Learn more')
        ->toContain('Also available')
        ->toContain('Omnichannel Integration')
        ->toContain('x-data="splitText" data-split-text="h2"')
        ->toContain('x-data="spotlightCard"');
});

it('shows the Indonesian texts on /id', function () {
    $this->get('/id')->assertOk()
        ->assertSee('Layanan Kami')
        ->assertSee('Tiga zona suhu')
        ->assertSee('Selengkapnya')
        ->assertSee('Juga tersedia');
});

it('uses the fleet side view cropped around its focus point for the Linehaul card', function () {
    $linehaul = collect(FrontendData::services('en'))->firstWhere('title', 'Linehaul Transport');

    expect($linehaul['image']['focus'])->toBe('70% 50%')
        ->and($linehaul['image']['large']['url'])->toContain('fleet-fe71-long-side');

    $this->get('/')->assertSee('style="object-position: 70% 50%"', false);
});

it('keeps at most three key points and links to #contact while there is no detail page', function () {
    Service::query()->where('slug', 'cold-storage')->firstOrFail()
        ->update(['key_points' => ['en' => "One\nTwo\nThree\nFour"]]);

    $cold = collect(FrontendData::services('en'))->firstWhere('title', 'Cold Storage');

    expect($cold['keyPoints'])->toBe(['One', 'Two', 'Three'])
        ->and($cold['url'])->toBeNull();

    $this->get('/')->assertSee('href="#contact" class="focus-visible:outline-none', false);
});

it('switches to three columns when there are more than four main services', function () {
    expect($this->get('/')->getContent())->not->toContain('xl:grid-cols-3');

    Service::query()->create(['title' => ['en' => 'Cross Docking'], 'slug' => 'cross-docking', 'icon' => 'box', 'is_featured' => true, 'is_active' => true]);

    expect($this->get('/')->getContent())->toContain('xl:grid-cols-3');
});
