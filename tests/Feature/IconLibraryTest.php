<?php

use App\Filament\Resources\LibraryIcons\Pages\CreateLibraryIcon;
use App\Filament\Resources\LibraryIcons\Pages\EditLibraryIcon;
use App\Filament\Resources\LibraryIcons\Pages\ListLibraryIcons;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\LibraryIcon;
use App\Models\Service;
use App\Models\User;
use App\Support\IconLibrary;
use App\Support\ServiceIcons;
use Livewire\Livewire;

/**
 * Admin icon library (Pustaka Ikon): stock sync, uploads, pickers, rendering.
 */
const CLEAN_ICON = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><circle cx="12" cy="12" r="8"/></svg>';

beforeEach(function () {
    seedSite();
    $this->actingAs(User::factory()->create());
});

it('copies the whole Ekasa stock into the library, batch 2 switched off except network', function () {
    $manifest = IconLibrary::manifest();

    expect(LibraryIcon::query()->count())->toBe(count($manifest['icons']))
        ->and(LibraryIcon::query()->where('source', LibraryIcon::SOURCE_EKASA)->count())->toBe(count($manifest['icons']));

    foreach ($manifest['review'] as $key) {
        expect(LibraryIcon::query()->where('key', $key)->value('is_active'))->toBeFalse($key);
    }

    expect(LibraryIcon::query()->where('key', 'network')->value('is_active'))->toBeTrue()
        ->and(ServiceIcons::options())->toHaveKey('network')
        ->and(ServiceIcons::options())->toHaveKey('chain-link')
        ->and(ServiceIcons::options())->not->toHaveKey('forklift');
});

it('keeps admin changes when the stock is synced again', function () {
    LibraryIcon::query()->where('key', 'forklift')->update(['is_active' => true, 'label_id' => 'Forklift gudang']);

    IconLibrary::sync();

    $icon = LibraryIcon::query()->where('key', 'forklift')->firstOrFail();
    expect($icon->is_active)->toBeTrue()
        ->and($icon->label_id)->toBe('Forklift gudang')
        ->and($icon->svg)->toBe(trim((string) file_get_contents(resource_path('icons/ekasa/forklift.svg'))));
});

it('gives Omnichannel Integration the network icon', function () {
    expect(Service::query()->where('slug', 'omnichannel-integration')->value('icon'))->toBe('network');

    $this->get('/')->assertOk()->assertSee(IconLibrary::find('network')['inner'], false);
});

it('shows the library in the admin as a grid with usage and filters', function () {
    Livewire::test(ListLibraryIcons::class)
        ->assertOk()
        ->assertCanSeeTableRecords(LibraryIcon::query()->where('key', 'network')->get())
        ->assertSee('Dipakai 1x')
        ->filterTable('is_active', false)
        ->assertCanSeeTableRecords(LibraryIcon::query()->where('key', 'forklift')->get())
        ->assertCanNotSeeTableRecords(LibraryIcon::query()->where('key', 'network')->get());

    $this->get('/admin/library-icons')->assertOk()->assertSee('Pustaka Ikon');
});

it('uploads a cleaned SVG icon that can be picked and appears on the site', function () {
    $dirty = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" onload="alert(1)"><title>x</title>'
        .'<script>alert(1)</script><style>path{stroke:red}</style>'
        .'<g><path d="M4 12h16" stroke="#ff0000" onclick="alert(1)"/></g><circle cx="12" cy="12" r="8" style="fill:red"/>'
        .'<image href="https://example.test/a.png"/><text x="1" y="1">A</text></svg>';

    Livewire::test(CreateLibraryIcon::class)
        ->fillForm([
            'label_en' => 'Custom box',
            'label_id' => 'Kotak khusus',
            'key' => 'custom-box',
            'category' => 'warehouse',
            'svg' => $dirty,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $icon = LibraryIcon::query()->where('key', 'custom-box')->firstOrFail();

    expect($icon->source)->toBe(LibraryIcon::SOURCE_UPLOAD)
        ->and($icon->svg)->toBe('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16"/><circle cx="12" cy="12" r="8"/></svg>')
        ->and(ServiceIcons::options())->toHaveKey('custom-box');

    $service = Service::query()->where('slug', 'cold-storage')->firstOrFail();

    Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
        ->fillForm(['icon' => 'custom-box'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get('/')->assertOk()->assertSee('<path d="M4 12h16"/><circle cx="12" cy="12" r="8"/>', false);
});

it('rejects SVG files that do not fit the library', function (string $svg, string $message) {
    Livewire::test(CreateLibraryIcon::class)
        ->fillForm(['label_en' => 'Bad', 'key' => 'bad-icon', 'category' => 'systems', 'svg' => $svg])
        ->call('create')
        ->assertHasFormErrors(['svg']);

    expect(fn () => IconLibrary::sanitize($svg))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'not svg' => ['<html></html>', 'bukan SVG'],
    'other viewBox' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><path d="M1 1h4"/></svg>', 'viewBox'],
    'entities' => ['<!DOCTYPE svg [<!ENTITY x "y">]><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M1 1h4"/></svg>', 'DOCTYPE'],
    'no shapes' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><text>A</text></svg>', 'tidak berisi bentuk'],
    'solid fill' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M1 1h4v4z" fill="#000"/></svg>', 'bentuk padat'],
    'transform' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M1 1h4" transform="rotate(4)"/></svg>', 'transform'],
    'script in geometry' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="javascript:alert(1)"/></svg>', 'tidak valid'],
]);

it('does not let an upload take the key of an interface icon or an existing icon', function (string $key) {
    Livewire::test(CreateLibraryIcon::class)
        ->fillForm(['label_en' => 'Menu', 'key' => $key, 'category' => 'systems', 'svg' => CLEAN_ICON])
        ->call('create')
        ->assertHasFormErrors(['key']);
})->with(['menu', 'x', 'snowflake', 'Not Kebab']);

it('keeps a used icon switched on and protects the Ekasa drawing', function () {
    $network = LibraryIcon::query()->where('key', 'network')->firstOrFail();
    $original = $network->svg;

    Livewire::test(EditLibraryIcon::class, ['record' => $network->getRouteKey()])
        ->assertFormFieldIsDisabled('is_active')
        ->assertFormFieldIsDisabled('key')
        ->assertFormFieldIsHidden('svg')
        ->assertActionHidden('delete')
        ->fillForm(['label_id' => 'Jaringan terpusat'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($network->refresh()->label_id)->toBe('Jaringan terpusat')
        ->and($network->svg)->toBe($original)
        ->and($network->is_active)->toBeTrue();
});

it('lets an unused uploaded icon be deleted, and hidden icons keep rendering where used', function () {
    $icon = LibraryIcon::query()->create([
        'key' => 'spare', 'label_en' => 'Spare', 'category' => 'systems', 'svg' => IconLibrary::sanitize(CLEAN_ICON), 'source' => LibraryIcon::SOURCE_UPLOAD,
    ]);

    Livewire::test(EditLibraryIcon::class, ['record' => $icon->getRouteKey()])
        ->assertActionVisible('delete')
        ->callAction('delete');

    expect(LibraryIcon::query()->where('key', 'spare')->exists())->toBeFalse();

    // A switched-off icon disappears from the pickers but still renders.
    LibraryIcon::query()->where('key', 'forklift')->update(['is_active' => false]);
    IconLibrary::flush();
    Service::query()->where('slug', 'cold-storage')->update(['icon' => 'forklift']);

    expect(ServiceIcons::options())->not->toHaveKey('forklift')
        ->and(ServiceIcons::has('forklift'))->toBeTrue();
    $this->get('/')->assertOk()->assertSee(IconLibrary::find('forklift')['inner'], false);
});

it('searches the icon picker by name, with a preview in each option', function () {
    $results = IconLibrary::htmlOptions('rantai');

    expect(array_keys(IconLibrary::options('salju')))->toContain('snowflake', 'thermometer-snowflake')
        ->and(IconLibrary::options('stroke'))->toBe([])
        ->and($results)->toBe([])
        ->and(IconLibrary::htmlOptions('kemitraan')['chain-link'])->toContain('<svg')->toContain('Kemitraan terhubung');
});
