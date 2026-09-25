<?php

use App\Filament\Resources\FleetVehicles\Pages\EditFleetVehicle;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\FleetVehicle;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use App\Support\ServiceBlocks;
use Database\Seeders\ServiceDataSeeder;

/**
 * Tahap 3, Bagian A: service detail data and the admin (Builder, shared data).
 */
beforeEach(function () {
    seedSite();
    $this->actingAs(User::factory()->create());
});

it('shows every new admin page', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    '/admin/services', '/admin/services/create',
    '/admin/fleet-vehicles', '/admin/fleet-vehicles/create',
    '/admin/locations', '/admin/locations/create',
    '/admin/rental-schemes', '/admin/rental-schemes/create',
    '/admin/transport-routes', '/admin/transport-routes/create',
    '/admin/industries', '/admin/industries/create',
    '/admin/faqs', '/admin/faqs/create',
]);

it('opens the edit page of every seeded record', function () {
    $models = [Service::class => 'services', FleetVehicle::class => 'fleet-vehicles', Location::class => 'locations'];

    foreach ($models as $model => $path) {
        foreach ($model::query()->get() as $record) {
            $this->get("/admin/{$path}/{$record->getKey()}/edit")->assertOk();
        }
    }
});

it('seeds the default block layouts of the four services, Omnichannel as draft', function () {
    $types = fn (string $slug): array => array_column((array) Service::query()->where('slug', $slug)->value('blocks'), 'type');

    expect($types('cold-storage'))->toBe(['hero_layanan', 'pita_spesifikasi', 'teks_gambar', 'zona_suhu', 'keunggulan', 'lokasi_gudang', 'industri', 'faq', 'cta_penutup'])
        ->and($types('linehaul-transport'))->toBe(['hero_layanan', 'pita_spesifikasi', 'teks_gambar', 'katalog_armada', 'skema_sewa', 'diagram_rute', 'cara_kerja', 'faq', 'cta_penutup'])
        ->and($types('hyperlocal-fulfilment'))->toBe(['hero_layanan', 'pita_spesifikasi', 'teks_gambar', 'lokasi_gudang', 'keunggulan', 'cara_kerja', 'faq', 'cta_penutup'])
        ->and($types('logistic-partnership'))->toBe(['hero_layanan', 'alur_end_to_end', 'teks_gambar', 'keunggulan', 'industri', 'faq', 'cta_penutup'])
        ->and(Service::query()->where('slug', 'omnichannel-integration')->value('detail_status'))->toBe(Service::DRAFT);

    $linehaul = Service::query()->where('slug', 'linehaul-transport')->firstOrFail();
    expect($linehaul->cardFleetVehicle?->model)->toBe('FE 71 Long')
        ->and($linehaul->card_focus_x)->toBe(70)
        ->and($linehaul->getMedia('card'))->toHaveCount(0)
        ->and($linehaul->slugFor('id'))->toBe('transportasi-linehaul');
});

it('creates a new service with the common block template and a slug per language', function () {
    Livewire::test(CreateService::class)
        ->fillForm([
            'title' => ['en' => 'Cross Docking', 'id' => 'Cross Docking'],
            'slugs' => ['en' => 'cross-docking', 'id' => 'cross-docking'],
            'slug' => 'cross-docking',
            'icon' => 'box',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $service = Service::query()->where('slug', 'cross-docking')->firstOrFail();

    expect(array_column($service->blocks, 'type'))->toBe(ServiceBlocks::DEFAULT_LAYOUT)
        ->and($service->detail_status)->toBe(Service::DRAFT)
        ->and($service->slugs)->toBe(['en' => 'cross-docking', 'id' => 'cross-docking']);
});

it('rejects a URL slug another service already uses in that language', function () {
    Livewire::test(CreateService::class)
        ->fillForm([
            'title' => ['en' => 'Cold Rooms'],
            'slugs' => ['en' => 'cold-rooms', 'id' => 'penyimpanan-dingin'],
            'slug' => 'cold-rooms',
            'icon' => 'box',
        ])
        ->call('create')
        ->assertHasFormErrors(['slugs.id']);
});

it('allows at most three key points', function () {
    $service = Service::query()->where('slug', 'cold-storage')->firstOrFail();

    Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
        ->fillForm(['key_points' => ['en' => "One\nTwo\nThree\nFour"]])
        ->call('save')
        ->assertHasFormErrors(['key_points.en']);
});

it('saves hidden blocks and a new block order from the Builder, and drops the Contoh marker', function () {
    $service = Service::query()->where('slug', 'logistic-partnership')->firstOrFail();
    expect($service->is_sample)->toBeTrue();

    $component = Livewire::test(EditService::class, ['record' => $service->getRouteKey()]);
    $state = $component->get('data.blocks');
    $keys = array_keys($state);

    // Hide the second block (alur_end_to_end) and move the FAQ block to the top.
    $state[$keys[1]]['data']['hidden'] = true;
    $faqKey = collect($state)->search(fn (array $block): bool => $block['type'] === 'faq');
    $state = [$faqKey => $state[$faqKey]] + $state;

    $component->set('data.blocks', $state)->call('save')->assertHasNoFormErrors();

    $service->refresh();
    expect($service->blocks[0]['type'])->toBe('faq')
        ->and(collect($service->blocks)->firstWhere('type', 'alur_end_to_end')['data']['hidden'])->toBeTrue()
        ->and($service->is_sample)->toBeFalse();
});

it('warns when a fleet side view is narrower than 1600 px', function () {
    config(['site.seed_service_images' => true]);
    $this->seed(ServiceDataSeeder::class);

    $vehicle = FleetVehicle::query()->where('model', 'FE 71 Long')->firstOrFail();
    // The real FE 71 file is exactly 1600 px (tests seed a smaller copy).
    $vehicle->getFirstMedia('side')->setCustomProperty('width', 1600)->save();

    Livewire::test(EditFleetVehicle::class, ['record' => $vehicle->refresh()->getRouteKey()])
        ->assertDontSee('Gambar tampak samping kurang lebar');

    $vehicle->getFirstMedia('side')->setCustomProperty('width', 1200)->save();

    Livewire::test(EditFleetVehicle::class, ['record' => $vehicle->refresh()->getRouteKey()])
        ->assertSee('Gambar tampak samping kurang lebar')
        ->assertSee('1200 px');
});

it('computes the fleet volume from the box when it is empty', function () {
    $vehicle = new FleetVehicle(['box_length' => 4.3, 'box_width' => 1.9, 'box_height' => 1.9]);

    expect($vehicle->volume())->toBe(15.5)
        ->and((new FleetVehicle(['box_length' => 4.3]))->volume())->toBeNull()
        ->and((new FleetVehicle(['volume_m3' => 20]))->volume())->toBe(20.0);
});
