<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\FaviconIco;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    config(['media-library.disk_name' => 'public', 'site.image_conversions' => 'off']);
    @unlink(FaviconIco::path());
    $this->actingAs(User::factory()->create());
});

afterEach(fn () => @unlink(FaviconIco::path()));

/**
 * Square PNG in one colour (GD).
 *
 * @param  array{0: int, 1: int, 2: int}  $rgb
 */
function solidPng(array $rgb, int $size = 512, string $name = 'favicon.png'): UploadedFile
{
    $image = imagecreatetruecolor($size, $size);
    imagefill($image, 0, 0, (int) imagecolorallocate($image, ...$rgb));
    ob_start();
    imagepng($image);

    return UploadedFile::fake()->createWithContent($name, (string) ob_get_clean());
}

/**
 * Entries of an ICO file: size => [width, height, PNG data].
 *
 * @return array<int, array{0: int, 1: int, 2: string}>
 */
function icoEntries(string $ico): array
{
    $header = unpack('vreserved/vtype/vcount', $ico);
    expect($header['reserved'])->toBe(0)->and($header['type'])->toBe(1);

    $entries = [];

    for ($i = 0; $i < $header['count']; $i++) {
        $entry = unpack('Cwidth/Cheight/Ccolors/Creserved/vplanes/vbpp/Vbytes/Voffset', $ico, 6 + 16 * $i);
        $entries[$entry['width']] = [$entry['width'], $entry['height'], substr($ico, $entry['offset'], $entry['bytes'])];
    }

    return $entries;
}

function uploadFavicon(UploadedFile $file): void
{
    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['favicon' => $file])
        ->call('save')
        ->assertHasNoErrors();
}

it('rebuilds favicon.ico (16, 32, 48 px) every time a PNG favicon is uploaded', function () {
    uploadFavicon(solidPng([15, 107, 70]));

    $entries = icoEntries((string) file_get_contents(FaviconIco::path()));
    expect(array_keys($entries))->toBe([16, 32, 48]);

    foreach ($entries as $size => [$width, $height, $png]) {
        $image = imagecreatefromstring($png);

        expect($width)->toBe($size)->and($height)->toBe($size)
            ->and(str_starts_with($png, "\x89PNG"))->toBeTrue()
            ->and(imagesx($image))->toBe($size)
            ->and(imagecolorsforindex($image, imagecolorat($image, intdiv($size, 2), intdiv($size, 2))))
            ->toMatchArray(['red' => 15, 'green' => 107, 'blue' => 70]);
    }

    // A second upload replaces the file.
    uploadFavicon(solidPng([200, 30, 30], name: 'favicon-red.png'));

    $png = icoEntries((string) file_get_contents(FaviconIco::path()))[32][2];
    expect(imagecolorsforindex(imagecreatefromstring($png), imagecolorat(imagecreatefromstring($png), 16, 16)))
        ->toMatchArray(['red' => 200, 'green' => 30, 'blue' => 30]);
})->skip(fn () => ! extension_loaded('gd'), 'GD is not available on this machine.');

it('fits a non-square favicon on a transparent square', function () {
    $image = imagecreatetruecolor(200, 100);
    imagefill($image, 0, 0, (int) imagecolorallocate($image, 15, 107, 70));
    ob_start();
    imagepng($image);

    $png = icoEntries((string) FaviconIco::fromImage((string) ob_get_clean()))[48][2];
    $icon = imagecreatefromstring($png);

    expect(imagecolorsforindex($icon, imagecolorat($icon, 24, 2))['alpha'])->toBe(127)
        ->and(imagecolorsforindex($icon, imagecolorat($icon, 24, 24)))->toMatchArray(['red' => 15, 'green' => 107, 'blue' => 70, 'alpha' => 0]);
})->skip(fn () => ! extension_loaded('gd'), 'GD is not available on this machine.');

it('copies an uploaded .ico as it is and refuses an SVG favicon', function () {
    $ico = (string) FaviconIco::fromImage((string) solidPng([15, 107, 70], 64)->getContent());

    // Same type a real .ico upload is detected as (a fake file guesses "application/ico" from the name).
    uploadFavicon(UploadedFile::fake()->createWithContent('favicon.ico', $ico)->mimeType('image/vnd.microsoft.icon'));
    expect(file_get_contents(FaviconIco::path()))->toBe($ico);

    // SVG is not accepted any more (it can carry script): the error shows and the old favicon stays.
    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['favicon' => UploadedFile::fake()->createWithContent('favicon.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><rect width="16" height="16"/></svg>')])
        ->call('save')
        ->assertHasFormErrors(['favicon']);

    expect(SiteSetting::current()->getFirstMedia('favicon')->mime_type)->toBeIn(['image/x-icon', 'image/vnd.microsoft.icon'])
        ->and(file_get_contents(FaviconIco::path()))->toBe($ico);
})->skip(fn () => ! extension_loaded('gd'), 'GD is not available on this machine.');

it('rebuilds favicon.ico from the current favicon with an artisan command', function () {
    SiteSetting::current()->addMedia(solidPng([15, 107, 70]))->toMediaCollection('favicon');
    @unlink(FaviconIco::path());

    $this->artisan('site:favicon-ico')->assertSuccessful();

    expect(array_keys(icoEntries((string) file_get_contents(FaviconIco::path()))))->toBe([16, 32, 48]);
})->skip(fn () => ! extension_loaded('gd'), 'GD is not available on this machine.');

it('never writes the real public/favicon.ico during tests', function () {
    expect(FaviconIco::path())->not->toBe(public_path('favicon.ico'));
});
