<?php

use App\Models\FleetVehicle;
use App\Models\Location;
use App\Models\Service;
use App\Models\SiteSetting;

/**
 * Tahap 3, Bagian D: the special blocks on the service detail pages.
 */
beforeEach(fn () => seedSite());

function useLayout(string $slug, string $type, array $changes): void
{
    $service = Service::query()->where('slug', $slug)->firstOrFail();
    $service->update(['blocks' => collect($service->blocks)->map(function (array $block) use ($type, $changes): array {
        if ($block['type'] === $type) {
            $block['data'] = [...$block['data'], ...$changes];
        }

        return $block;
    })->all()]);
}

/** The seeder no longer ships example warehouses (only the real office), so these tests create their own. */
function makeSites(): void
{
    foreach ([
        ['Cakung', 'warehouse', 'Jakarta Timur', -6.1795, 106.9430, ['frozen', 'chiller']],
        ['Pulo Gadung', 'hub', 'Jakarta Timur', -6.1985, 106.9050, ['chiller', 'cool']],
        ['Tanjung Priok', 'warehouse', 'Jakarta Utara', -6.1105, 106.8830, ['frozen', 'chiller', 'ambient']],
    ] as $index => [$name, $kind, $area, $lat, $lng, $zones]) {
        Location::query()->create([
            'name' => $name,
            'kind' => $kind,
            'city' => 'Jakarta',
            'area' => $area,
            'address' => "Kawasan contoh {$name}, {$area}",
            'lat' => $lat,
            'lng' => $lng,
            'temperature_zones' => $zones,
            'commodities' => ['en' => 'Frozen goods', 'id' => 'Barang beku'],
            'capacity' => ['en' => '1,000 pallets', 'id' => '1.000 palet'],
            'opening_hours' => ['en' => 'Open 24 hours', 'id' => 'Buka 24 jam'],
            'sort_order' => $index + 1,
            'is_active' => true,
        ]);
    }
}

it('draws the temperature zones on a thermometer that is complete without JS', function () {
    $html = $this->get('/services/cold-storage')->assertOk()->getContent();
    $section = substr($html, (int) strpos($html, 'id="zona-suhu"'));

    expect($section)->toContain('class="thermo-fill"')
        ->toContain('data-draw')
        ->toContain('zone-frozen')
        ->toContain('-25 … -18 °C')
        ->toContain('Frozen meat, seafood, ice cream');

    $this->get('/id/layanan/penyimpanan-dingin')->assertSee('Daging beku, hasil laut, es krim');
});

it('shows the warehouse map, cards and Google Maps links, and loads Leaflet only on demand', function () {
    makeSites();
    $html = $this->get('/services/cold-storage')->getContent();
    $section = substr($html, (int) strpos($html, 'id="lokasi-gudang"'), 20000);

    expect($section)->toContain('x-data="locationMap(')
        ->toContain('Cakung')
        ->toContain('Tanjung Priok')
        ->not->toContain('>Pulo Gadung<')
        ->toContain('https://www.google.com/maps/search/?api=1&amp;query=')
        ->toContain('x-data="animatedList" data-animated-list')
        ->toContain('x-cloak')
        ->and($html)->not->toContain('leaflet');

    // Hyperlocal shows the city hub.
    $this->get('/services/hyperlocal-fulfilment')->assertSee('Pulo Gadung')->assertSee('City hub');
});

it('drops the locations block when no location matches', function () {
    makeSites();
    Location::query()->where('kind', 'hub')->update(['is_active' => false]);

    $this->get('/services/hyperlocal-fulfilment')->assertOk()->assertDontSee('id="lokasi-gudang"', false);
});

it('lists every truck in the showcase (all visible without JS) with tabs, live region and WhatsApp', function () {
    SiteSetting::current()->update(['whatsapp_number' => '0812 3456 7890']);

    $html = $this->get('/services/linehaul-transport')->getContent();
    $section = substr($html, (int) strpos($html, 'id="katalog-armada"'), 30000);

    expect($section)->toContain('FE 71 Long')
        ->toContain('FE 74 Long')
        ->toContain('role="tablist"')
        ->toContain('role="tabpanel"')
        ->toContain('aria-live="polite"')
        ->toContain('https://wa.me/6281234567890?text=Hello%2C%20I%20would%20like%20to%20ask%20about%20the%20availability%20of%20the%20FE%2071%20Long.')
        ->toContain('2,500 kg')
        ->toContain('4.3 × 1.9 × 1.9 m');

    $this->get('/id/layanan/transportasi-linehaul')->assertSee('Tanya ketersediaan')->assertSee('4.000 kg');
});

it('hides empty fleet specs and asks via #contact without WhatsApp', function () {
    FleetVehicle::query()->update(['capacity_kg' => null]);

    $html = $this->get('/services/linehaul-transport')->getContent();
    $section = substr($html, (int) strpos($html, 'id="katalog-armada"'), 30000);

    expect($section)->not->toContain('Payload')
        ->toContain('Box volume')
        ->toContain('href="/#contact"');
});

it('switches the fleet block to a grid with a type filter', function () {
    FleetVehicle::query()->where('model', 'FE 74 Long')->update(['type' => 'dry']);
    useLayout('linehaul-transport', 'katalog_armada', ['layout' => 'grid']);

    $html = $this->get('/services/linehaul-transport')->getContent();
    $section = substr($html, (int) strpos($html, 'id="katalog-armada"'), 30000);

    expect($section)->not->toContain('role="tablist"')
        ->toContain("type = 'dry'")
        ->toContain('Refrigerated')
        ->toContain('Dry');
});

it('compares the rental schemes and draws the routes with travel times', function () {
    $html = $this->get('/id/layanan/transportasi-linehaul')->getContent();

    expect($html)->toContain('Unit + sopir')
        ->toContain('Bulanan')
        ->toContain('Truk beserta sopir yang terlatih')
        ->toContain('class="route-line')
        ->toContain('pathLength="1"')
        ->toContain('Surabaya')
        ->toContain('12–16 jam');
});

it('shows the end-to-end flow with a line that fills once visible', function () {
    $html = $this->get('/services/logistic-partnership')->getContent();
    $section = substr($html, (int) strpos($html, 'id="alur-end-to-end"'), 20000);

    expect($section)->toContain('class="flow-fill')
        ->toContain('Inbound')
        ->toContain('Last mile')
        ->and(substr_count($section, '<li class="flex gap-5'))->toBe(5);
});
